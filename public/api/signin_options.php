<?php
declare(strict_types=1);

/**
 * POST /api/signin_options.php
 * Body: { "studentId": "...", "email": "...", "deviceId": "...", "sessionId": "..." }
 *
 * SCHEMA ASSUMPTIONS (adjust if these don't match the real tables):
 *   attendance_logs: session_Id varchar(10), signins JSON, total_signins int
 *   "sessionId" is the short attendance-session code the student is
 *   signing into (posted by the client — e.g. scanned from a QR code or
 *   entered on screen). It is NOT the PHP session id.
 *
 * The student's identity comes from the authenticated session ($ses), same
 * as register_options.php. Before minting a WebAuthn assertion challenge
 * (navigator.credentials.get()) this fails fast if the student already has
 * an attendance record for this session, so the browser never prompts for
 * Face/Touch ID pointlessly. The raw challenge and pending sign-in context
 * are stored server-side so signin_verify.php can tie the credential back
 * to this exact student, device and attendance session.
 */

header('Content-Type: application/json');

require_once __DIR__ . '../../config/bytes.php';
require_once __DIR__ . '../../config/config.php';
require_once __DIR__ . '../../config/webauthn.php';
/* Session Manager file */
require_once __DIR__ . '../../config/sessionManager.php';

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

/* The WebAuthn options object has its own required shape (challenge,
 * allowCredentials, rpId, ...), so instead of merging success/message
 * fields into it directly, nest it under "options" and keep the envelope
 * consistent with the other endpoints. */
function respond_options(array $options, string $message): void
{
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'options' => $options,
        'current_timestamp' => current_dated(),
    ]);
    exit;
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
$studentIdHex  = $ses->get('usrID');
$studentMail   = $ses->get('usrEmaile');
$studentDevice = $ses->get('studentDevID');
$studentId     = hexdec($studentIdHex);

if (!is_int($studentId) || $studentMail === '') {
    /* Session details are missing/invalid. Destroy it; the student must start again. */
    $ses->destroy();
    respond_error(401, 'Invalid session details. Please start again.');
}

/* Data posted by the sign-in page */
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    respond_error(400, 'Invalid request body');
}

$postedStudentId = str_field($body, 'studentId');
$email = str_field($body, 'email'); // was never read — $email was undefined below
$deviceId = str_field($body, 'deviceId');
$sessionId = str_field($body, 'sessionId');

/* Posted identity must tally with the session (postedStudentId is decimal) */
if (
    $postedStudentId === ''
    || !ctype_digit($postedStudentId)
    || (int)$postedStudentId !== $studentId
    || $email === ''
    || strcasecmp($email, $studentMail) !== 0
) {
    $ses->destroy();
    respond_error(403, 'Details do not match your session. Please start again.');
}

if ($sessionId === '' || strlen($sessionId) > 10) {
    respond_error(400, 'Missing or invalid attendance session ID');
}

if ($deviceId !== $studentDevice) {
    // The session is authoritative for device identity — same rule as
    // signin_verify.php. Log the mismatch rather than silently swallowing it.
    error_log('signin_options: posted deviceId differs from session for student ' . $studentIdHex);
    $deviceId = $studentDevice;
}

try {
    // The device must be the one already bound to this student (same rule
    // as register_options.php).
    $stmt = $pdo->prepare('SELECT deviceID FROM studentsDetails WHERE userID = ?');
    $stmt->execute([$studentIdHex]);
    $student = $stmt->fetch();

    if (!$student) {
        respond_error(404, 'Student record not found');
    }

    $boundDeviceId = trim((string)($student['deviceID'] ?? ''));
    // Unlike register_options.php, sign-in can never legitimately see an
    // empty bound device: a passkey only exists once a device has already
    // been bound. Treat empty the same as a mismatch rather than letting
    // it through.
    if ($boundDeviceId === '' || !hash_equals($boundDeviceId, $deviceId)) {
        respond_error(403, 'This is not the device registered to your account');
    }

    // -------------------------------------------------------------
    // 1) Has this student already signed into this attendance session?
    // -------------------------------------------------------------
    $stmt = $pdo->prepare('SELECT signins FROM attendance_logs WHERE session_Id = ? LIMIT 1');
    $stmt->execute([$sessionId]);
    $attendance = $stmt->fetch();

    if (!$attendance) {
        respond_error(404, 'No active attendance session found');
    }

    $signins = json_decode((string)($attendance['signins'] ?? '[]'), true);
    if (!is_array($signins)) {
        $signins = [];
    }

    if (in_array($studentIdHex, $signins, true)) {
        respond_error(409, 'You are already signed in for this session');
    }

    // -------------------------------------------------------------
    // 2) Any prior auth_logs row for this student + session?
    // -------------------------------------------------------------
    $stmt = $pdo->prepare('SELECT 1 FROM auth_logs WHERE userID = ? AND session_Id = ? LIMIT 1');
    $stmt->execute([$studentIdHex, $sessionId]);
    if ($stmt->fetch()) {
        respond_error(409, 'You are already signed in for this session');
    }

    // -------------------------------------------------------------
    // 3) The student needs a registered passkey, and it must still be
    //    paired with this exact device. student_passkey_credentials.userID
    //    is a primary key, so there is at most one credential per student.
    // -------------------------------------------------------------
    $stmt = $pdo->prepare(
        'SELECT credential_id, device_fingerprint, attestation_format
         FROM student_passkey_credentials
         WHERE userID = ?
         LIMIT 1'
    );
    $stmt->execute([$studentIdHex]);
    $cred = $stmt->fetch();

    if (!$cred) {
        respond_error(404, 'No passkey is registered for this account yet. Please register one first.');
    }

    // For 'none'/'packed-self' attestation (the common case for platform
    // authenticators — Touch ID, Android biometrics), device_fingerprint
    // was computed at registration as hash('device:' . deviceId) — see
    // register_verify.php / attestation.php. Recomputing and comparing it
    // here, in addition to the studentsDetails.deviceID check above, means
    // both device-identity records have to agree before we even issue a
    // challenge. Cert-derived fingerprints (hardware keys / TPM) can't be
    // recomputed from deviceId, so they rely on the deviceID check plus
    // the DB's UNIQUE KEY on device_fingerprint instead.
    if (in_array($cred['attestation_format'], ['none', 'packed-self'], true)) {
        $expectedFingerprint = hash('sha256', 'device:' . $deviceId);
        if (!hash_equals($expectedFingerprint, (string)$cred['device_fingerprint'])) {
            respond_error(403, 'This passkey is not bound to this device');
        }
    }

    $options = WebAuthn::buildAuthenticationOptions([b64url_decode($cred['credential_id'])]);

    // Persist challenge + pending sign-in state server-side only.
    $ses->set('webauthn_signin', [
        'student_id' => $studentIdHex,
        'email'      => $studentMail,
        'device_id'  => $deviceId,
        'session_id' => $sessionId,
        'challenge'  => b64url_encode($options['_challengeRaw']),
        'expires_at' => time() + CHALLENGE_TTL,
    ]);

    unset($options['_challengeRaw']); // never send raw challenge bytes twice
    respond_options($options, 'Confirm with your fingerprint, face, or device passkey to sign in.');
} catch (Throwable $e) {
    error_log('signin_options error: ' . $e->getMessage());
    respond_error(500, 'Could not start sign-in: ' . $e->getMessage());
}