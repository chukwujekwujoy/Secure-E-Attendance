<?php
declare(strict_types=1);

/**
 * POST /api/signin_verify.php
 * Body (as sent by the sign-in page):
 * {
 *   studentId, email, deviceId, sessionId,
 *   credential: { id, rawId, type, response: { clientDataJSON, authenticatorData, signature, userHandle } }
 * }
 *
 * The student's identity comes from the authenticated session ($ses).
 * Verifies the assertion against the challenge issued by
 * signin_options.php and the public key already on file for that
 * credential (from registration), then — only once that succeeds —
 * records attendance:
 *   - appends studentIdHex to attendance_logs.signins (JSON array) for
 *     this session and increments attendance_logs.total_signins
 *   - inserts a row into auth_logs (userID, session_Id, ip_address, user_agent)
 *
 * See the schema-assumption note at the top of signin_options.php.
 */

header('Content-Type: application/json');

/* Session Manager file */
require_once __DIR__ . '../../config/sessionManager.php';
require_once __DIR__ . '../../config/config.php';
require_once __DIR__ . '../../config/bytes.php';
require_once __DIR__ . '../../config/webauthn.php';

use SessionManager\SessionManager;

function current_dated(): string
{
	return (new DateTimeImmutable('now', new DateTimeZone('+01:00')))->format('Y-m-d H:i:s');
}

function respond_error(int $status, string $message, array $extra = []): void
{
    http_response_code($status);
    // signin.js reads "message"; "error" is kept for other callers.
    echo json_encode(array_merge([
		'success' => false,
		'error' => $message,
		'message' => $message,
		'current_timestamp' => current_dated()
	], $extra));
    exit;
}

function respond_success(array $data, string $message = ''): void
{
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'current_timestamp' => current_dated()
    ]);
    exit;
}

/** Roll back any open transaction, then send the error. */
function abort_tx(PDO $pdo, int $status, string $message): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond_error($status, $message);
}

function str_field(array $src, string $key): string
{
    $v = $src[$key] ?? '';
    return (is_string($v) || is_int($v)) ? trim((string)$v) : '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error(405, 'Method not allowed');
}

/* Initialize + start session */
$ses = new SessionManager(600);
$ses->start();

/**
 * Student Auth
 *
 * This endpoint expects a student who is already signed in, with their
 * identity stored in $ses.
 */

/* Check if session has expired */
if ($ses->isExpired()) {
    respond_error(401, 'Session expired. Please start again.');
}

/* Check if session is authenticated */
if ($ses->get('authenticated') !== true) {
    respond_error(401, 'Invalid session. Please start again.');
}

/* Verify the client fingerprint (destroys the session on mismatch) */
$ses->bindToClient();

/* Authenticated identity, straight from the session */
$studentIdHex = $ses->get('usrID');
$studentMail = $ses->get('usrEmaile');
$sessionDevice = $ses->get('studentDevID');
$studentId = hexdec($studentIdHex);

if (!is_int($studentId) || $studentMail === '') {
    /* Session details are missing/invalid. Destroy it; the student must start again. */
    $ses->destroy();
    respond_error(401, 'Invalid session details. Please start again.');
}

// ---------------------------------------------------------------------
// Pending sign-in from signin_options.php (single use)
// ---------------------------------------------------------------------
$pending = $ses->get('webauthn_signin');
$ses->set('webauthn_signin', null); // a failed attempt must start over with a fresh challenge

if (!is_array($pending)) {
    respond_error(400, 'No sign-in in progress');
}
if (time() > (int)$pending['expires_at']) {
    respond_error(400, 'Sign-in challenge expired, please retry');
}
if (strcasecmp((string)($pending['student_id'] ?? ''), $studentIdHex) !== 0) {
    respond_error(400, 'Sign-in details changed, please retry');
}
if (!is_string($pending['challenge'] ?? null) || $pending['challenge'] === '') {
    respond_error(400, 'No sign-in in progress');
}

// ---------------------------------------------------------------------
// Request body
// ---------------------------------------------------------------------
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    respond_error(400, 'Invalid request body');
}

$credential = $body['credential'] ?? null;
if (!is_array($credential)) {
    respond_error(400, 'Missing credential');
}

$postedStudentId = str_field($body, 'studentId');
$email = str_field($body, 'email');
$postedDeviceId = str_field($body, 'deviceId');
$sessionId = str_field($body, 'sessionId');

/* Posted identity must tally with the session (postedStudentId is decimal) */
if (
    $postedStudentId === ''
    || !ctype_digit($postedStudentId)
    || (int)$postedStudentId !== $studentId
    || strcasecmp($email, $studentMail) !== 0
) {
    $ses->destroy();
    respond_error(403, 'Details do not match your session. Please start again.');
}

// Must be signing into the same attendance session the challenge was issued for.
if ($sessionId === '' || strcasecmp($sessionId, (string)($pending['session_id'] ?? '')) !== 0) {
    respond_error(400, 'Sign-in details changed, please retry');
}

/*
 * Device: the session is authoritative (same rule as register_verify.php).
 */
if ($sessionDevice !== '' && !hash_equals($sessionDevice, $postedDeviceId)) {
    error_log('signin_verify: posted deviceId differs from session for student ' . $studentIdHex);
}
$deviceId = $sessionDevice;

// Same device as when the challenge was issued.
if ($deviceId === '' || !hash_equals((string)$pending['device_id'], $deviceId)) {
    respond_error(400, 'Sign-in details changed unfortunately, please retry');
}

// ---------------------------------------------------------------------
// WebAuthn assertion verification
// ---------------------------------------------------------------------
$credentialIdRaw = b64url_decode((string)($credential['rawId'] ?? ''));
$credentialIdB64 = b64url_encode($credentialIdRaw);

try {
    // The device must still be the one bound to this student.
    $stmt = $pdo->prepare('SELECT deviceID FROM studentsDetails WHERE userID = ?');
    $stmt->execute([$studentIdHex]);
    $student = $stmt->fetch();
    if (!$student) {
        respond_error(404, 'Student record not found');
    }
    $boundDeviceId = trim((string)($student['deviceID'] ?? ''));
    // Same rule as signin_options.php: empty means no device was ever
    // bound, which shouldn't be possible alongside an existing passkey —
    // treat it as a mismatch, not a pass-through.
    if ($boundDeviceId === '' || !hash_equals($boundDeviceId, $deviceId)) {
        respond_error(403, 'This is not the device registered to your account');
    }

    // Look up the credential the browser says it used, scoped to this
    // student — never trust the credential id alone without the userID match.
    $stmt = $pdo->prepare(
        'SELECT credential_id, public_key_pem, cose_alg, sign_count, device_fingerprint, attestation_format
         FROM student_passkey_credentials
         WHERE userID = ? AND credential_id = ?
         LIMIT 1'
    );
    $stmt->execute([$studentIdHex, $credentialIdB64]);
    $stored = $stmt->fetch();

    if (!$stored) {
        respond_error(403, 'This passkey is not registered to your account');
    }

    // Cross-check the credential's own device_fingerprint against this
    // device, in addition to the studentsDetails.deviceID check above —
    // see the matching comment in signin_options.php for why both are
    // checked. This runs before the (more expensive) signature
    // verification below, so a mismatched device fails fast.
    if (in_array($stored['attestation_format'], ['none', 'packed-self'], true)) {
        $expectedFingerprint = hash('sha256', 'device:' . $deviceId);
        if (!hash_equals($expectedFingerprint, (string)$stored['device_fingerprint'])) {
            respond_error(403, 'This passkey is not bound to this device');
        }
    }

    $result = WebAuthn::verifyAuthentication(
        $credential,
        b64url_decode($pending['challenge']),
        $stored['public_key_pem'],
        (int)$stored['cose_alg'],
        (int)$stored['sign_count']
    );
} catch (WebAuthnException $e) {
    error_log('signin_verify rejected for student ' . $studentIdHex . ': ' . $e->getMessage());
    respond_error(400, 'Sign-in failed: ' . $e->getMessage());
} catch (Throwable $e) {
    error_log('signin_verify error: ' . $e->getMessage());
    respond_error(500, 'Oops! Sign-in failed: ' . $e->getMessage());
}

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
if (is_string($ip) && str_contains($ip, ',')) {
    // X-Forwarded-For can be a client,proxy1,proxy2 chain — take the first hop.
    $ip = trim(explode(',', $ip)[0]);
}
$userAgent = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

// ---------------------------------------------------------------------
// Persist
// ---------------------------------------------------------------------
try {
    $pdo->beginTransaction();

    // Bump the stored signature counter (and last-used timestamp)
    // regardless of what happens below, so a replayed/cloned authenticator
    // response can't be reused even if the attendance write itself fails.
    $stmt = $pdo->prepare(
        'UPDATE student_passkey_credentials
         SET sign_count = ?, last_used_at = NOW()
         WHERE credential_id = ?'
    );
    $stmt->execute([$result['signCount'], $credentialIdB64]);

    // Lock the attendance session row so concurrent sign-ins for it serialise.
    $stmt = $pdo->prepare('SELECT signins, total_signins FROM attendance_logs WHERE session_Id = ? FOR UPDATE');
    $stmt->execute([$sessionId]);
    $attendance = $stmt->fetch();

    if (!$attendance) {
        abort_tx($pdo, 404, 'No active attendance session found');
    }

    $signins = json_decode((string)($attendance['signins'] ?? '[]'), true);
    if (!is_array($signins)) {
        $signins = [];
    }

    // Re-check for a duplicate inside the lock (the pre-check in
    // signin_options.php is best-effort only, this one is authoritative).
    if (in_array($studentIdHex, $signins, true)) {
        abort_tx($pdo, 409, 'You are already signed in for this session');
    }

    $stmt = $pdo->prepare('SELECT 1 FROM auth_logs WHERE userID = ? AND session_Id = ? LIMIT 1');
    $stmt->execute([$studentIdHex, $sessionId]);
    if ($stmt->fetch()) {
        abort_tx($pdo, 409, 'You are already signed in for this session');
    }

    $signins[] = $studentIdHex;

    $stmt = $pdo->prepare(
        'UPDATE attendance_logs
         SET signins = ?, total_signins = total_signins + 1
         WHERE session_Id = ?'
    );
    $stmt->execute([json_encode(array_values($signins)), $sessionId]);

    $stmt = $pdo->prepare(
        'INSERT INTO auth_logs (userID, session_Id, ip_address, user_agent) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$studentIdHex, $sessionId, $ip, $userAgent]);

    $pdo->commit();

    // Was: echo json_encode(['status' => 'ok']); — bypassed the
    // success/message envelope every other endpoint uses, so the sign-in
    // page had no confirmation text to show.
    respond_success(['status' => 'ok'], "You're signed in — attendance recorded.");
} catch (PDOException $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('signin_verify persist error: ' . $e->getMessage());

    // SQLSTATE 23000 covers several different failures; the MySQL driver
    // code tells them apart. This assumes a unique index on
    // auth_logs(userID, session_Id) — add one if it doesn't exist yet, to
    // close the race between the SELECT check above and this INSERT.
    $driverCode = (int)($e->errorInfo[1] ?? 0);
    if ($driverCode === 1062) {
        respond_error(409, 'You are already signed in for this session');
    }
    respond_error(500, 'Could not record sign-in: ' . $e->getMessage());
} catch (Throwable $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('signin_verify persist error: ' . $e->getMessage());
    respond_error(500, 'Could not record sign-in: ' . $e->getMessage());
}