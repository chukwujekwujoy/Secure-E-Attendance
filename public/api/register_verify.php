<?php
declare(strict_types=1);

/**
 * POST /api/register_verify.php
 * Body (as sent by register-passkey.js):
 * {
 *   studentId, email, deviceId,
 *   userAgent: { os, browser, lang, userAgent, allowed },
 *   credential: { id, rawId, type, response: { clientDataJSON, attestationObject, transports } }
 * }
 *
 * The student's identity comes from the authenticated session ($ses).
 * Verifies the attestation against the challenge issued by
 * register_options.php, checks the student/device/browser, converts the COSE
 * public key to PEM, and stores the new passkey in student_passkey_credentials.
 *
 * Attestation policy: 'none' and self-attested 'packed' (no x5c) are
 * accepted — this is standard for passkeys, since most platform
 * authenticators (Touch ID, Android fingerprint/face unlock) return
 * 'none' regardless of what conveyance was requested. Those formats give
 * no certificate-derived device signal, so when WebAuthn::verifyRegistration()
 * returns a null deviceFingerprint, this file falls back to its own
 * app-level device identifier ($deviceId), which is already authenticated
 * against the session and checked for uniqueness below.
 */

header('Content-Type: application/json');

/* Session Manager file */
require_once __DIR__ . '../../config/sessionManager.php';
require_once __DIR__ . '../../config/config.php';
require_once __DIR__ . '../../config/bytes.php';
require_once __DIR__ . '../../config/webauthn.php';

use SessionManager\SessionManager;

function respond_error(int $status, string $message): void
{
    http_response_code($status);
    // register-passkey.js reads "message"; "error" is kept for other callers.
    echo json_encode(['error' => $message, 'message' => $message]);
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

/**
 * Server-side twin of parseUserAgent() in register-passkey.js. Runs on the
 * User-Agent header rather than trusting the client-reported "allowed" flag.
 */
function parse_user_agent(string $ua): array
{
    $os = 'Unknown OS';
    if (preg_match('/Android/i', $ua)) {
        $os = 'Android';
    } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
        $os = 'iOS';
    } elseif (preg_match('/Windows NT/i', $ua)) {
        $os = 'Windows';
    } elseif (preg_match('/Macintosh|Mac OS X/i', $ua)) {
        $os = 'macOS';
    } elseif (preg_match('/Linux/i', $ua)) {
        $os = 'Linux';
    } elseif (preg_match('/CrOS/i', $ua)) {
        $os = 'ChromeOS';
    }

    $browser = 'Unknown Browser';
    if (preg_match('#Edg/#i', $ua)) {
        $browser = 'Edge';
    } elseif (preg_match('#OPR/#i', $ua)) {
        $browser = 'Opera';
    } elseif (preg_match('#CriOS/|Chrome/#i', $ua)) {
        $browser = 'Chrome';
    } elseif (preg_match('#FxiOS/|Firefox/#i', $ua)) {
        $browser = 'Firefox';
    } elseif (preg_match('#Safari/#i', $ua)) {
        $browser = 'Safari';
    }

    $allowed = ($os === 'Android' && $browser === 'Chrome')
        || ($os === 'iOS' && ($browser === 'Chrome' || $browser === 'Safari'));

    return ['os' => $os, 'browser' => $browser, 'allowed' => $allowed];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error(405, 'Method not allowed');
}

/* Initialize + start session */
$ses = new SessionManager(900);
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
$studentMail  = $ses->get('usrEmaile');
$sessionDevice = $ses->get('studentDevID');
$studentId    = hexdec($studentIdHex);

if (!is_int($studentId) || $studentMail === '') {
    /* Session details are missing/invalid. Destroy it; the student must start again. */
    $ses->destroy();
    respond_error(401, 'Invalid session details. Please start again.');
}

// ---------------------------------------------------------------------
// Pending registration from register_options.php (single use)
// ---------------------------------------------------------------------
$pending = $ses->get('webauthn_reg');
$ses->set('webauthn_reg', null); // a failed attempt must start over with a fresh challenge

if (!is_array($pending)) {
    respond_error(400, 'No registration in progress');
}
if (time() > (int)$pending['expires_at']) {
    respond_error(400, 'Registration challenge expired, please retry');
}
if (strcasecmp((string)($pending['student_id'] ?? ''), $studentIdHex) !== 0) {
    respond_error(400, 'Registration details changed, please retry');
}
if (!is_string($pending['challenge'] ?? null) || $pending['challenge'] === '') {
    respond_error(400, 'No registration in progress');
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

/*
 * Device: the session is authoritative (same rule as register_options.php).
 * Only when the session has no device yet (first registration) is the posted
 * one used, and it is bound to the account below.
 */
if ($sessionDevice !== '' && !hash_equals($sessionDevice, $postedDeviceId)) {
    error_log('register_verify: posted deviceId differs from session for student ' . $studentIdHex);
}
$deviceId = $sessionDevice;

// Same device as when the challenge was issued.
if ($deviceId === '' || !hash_equals((string)$pending['device_id'], $deviceId)) {
    respond_error(400, 'Registration details changed unfortunately, please retry');
}

// ---------------------------------------------------------------------
// Browser / OS check (server-side, from the real User-Agent header)
// ---------------------------------------------------------------------
$serverUa = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
$env = parse_user_agent($serverUa);
if (!$env['allowed']) {
    respond_error(403, "Passkey registration isn't supported on this browser/device. Please use Chrome on Android or Safari/Chrome on iOS.");
}

// The client also reports its own UA; a mismatch is worth logging.
$clientUa = is_array($body['userAgent'] ?? null) ? ($body['userAgent']['userAgent'] ?? null) : null;
if (is_string($clientUa) && $clientUa !== $serverUa) {
    error_log('register_verify: client-reported UA differs from header for student ' . $studentId);
}

// ---------------------------------------------------------------------
// WebAuthn attestation verification
// ---------------------------------------------------------------------
try {
    $result = WebAuthn::verifyRegistration($credential, b64url_decode($pending['challenge']));
} catch (WebAuthnException $e) {
	error_log('register_verify rejected for student ' . $studentIdHex . ': ' . $e->getMessage());
    respond_error(400, 'Registration failed: ' . $e->getMessage());
} catch (Throwable $e) {
    error_log('register_verify error: ' . $e->getMessage());
    respond_error(500, 'Oops! Registration failed: ' . $e->getMessage());
}

// No hardware attestation (fmt=none or self-attested packed) → no
// certificate-derived fingerprint. Fall back to the app's own device
// identifier, which is already authenticated against the session and the
// pending-registration record above, and checked for uniqueness below.
$deviceFingerprint = $result['deviceFingerprint'] ?? hash('sha256', 'device:' . $deviceId);

// ---------------------------------------------------------------------
// Persist
// ---------------------------------------------------------------------

try {
    $pdo->beginTransaction();

    // Lock the student row so concurrent registrations for the same student serialise.
	// Looked up by the session's usrID
    $stmt = $pdo->prepare('SELECT userID, userEmil, deviceID FROM studentsDetails WHERE userID = ? FOR UPDATE');
    $stmt->execute([$studentIdHex]);
    $student = $stmt->fetch();
    if (!$student) {
        abort_tx($pdo, 404, 'Student record not found');
    }

    // Session email must match the database record.
    if (strcasecmp(trim((string)($student['userEmil'] ?? '')), $studentMail) !== 0) {
        abort_tx($pdo, 403, 'Your account email does not match our records');
    }

    // Device must match the one bound to this student (if any)...
    $boundDeviceId = trim((string)($student['deviceID'] ?? ''));
    if ($boundDeviceId !== '' && !hash_equals($boundDeviceId, $deviceId)) {
        abort_tx($pdo, 403, 'This is not the device registered to your account');
    }

    // ...and must not already belong to a different student.
    $stmt = $pdo->prepare('SELECT 1 FROM studentsDetails WHERE deviceID = ? AND userID <> ? LIMIT 1');
    $stmt->execute([$deviceId, $studentIdHex]);
    if ($stmt->fetch()) {
        abort_tx($pdo, 409, 'This device is already linked to another student');
    }

    // Reject if this exact credential is already registered (should be
    // rare given excludeCredentials, but enforce it at the DB level too).
    $credentialIdB64 = b64url_encode($result['credentialId']);
    $stmt = $pdo->prepare('SELECT 1 FROM student_passkey_credentials WHERE credential_id = ? LIMIT 1');
    $stmt->execute([$credentialIdB64]);
    if ($stmt->fetch()) {
        abort_tx($pdo, 409, 'This passkey is already registered');
    }

    // Enforce: no physical device may hold more than one passkey, across
    // ALL students. See the caveats about batch/none/self attestation in
    // attestation.php — when $deviceFingerprint came from the app-level
    // $deviceId fallback (no hardware attestation) rather than a
    // certificate hash, this is only as trustworthy as $deviceId itself.
    $stmt = $pdo->prepare('SELECT userID FROM student_passkey_credentials WHERE device_fingerprint = ? LIMIT 1');
    $stmt->execute([$deviceFingerprint]);
    if ($existingDeviceUse = $stmt->fetch()) {
        abort_tx(
            $pdo,
            409,
            'This device already has a registered passkey' .
            (strcasecmp((string)$existingDeviceUse['userID'], $studentIdHex) === 0
                ? '.'
                : ' (registered to a different account).')
        );
    }

    $stmt = $pdo->prepare(
        'INSERT INTO student_passkey_credentials
            (userID, credential_id, public_key_pem, cose_alg, sign_count, aaguid,
             device_fingerprint, attestation_format)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $studentIdHex,
        $credentialIdB64,
        $result['publicKeyPem'],
        $result['coseAlg'],
        $result['signCount'],
        bin2hex($result['aaguid'] ?? ''),
        $deviceFingerprint,
        $result['attestationFormat'],
    ]);

    // First registration for this student: bind the device to the account.
    if ($boundDeviceId === '') {
        $stmt = $pdo->prepare('UPDATE studentsDetails SET deviceID = ? WHERE userID = ?');
        $stmt->execute([$deviceId, $studentId]);
    }

    $pdo->commit();

    echo json_encode(['status' => 'ok']);
} catch (PDOException $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('register_verify persist error: ' . $e->getMessage());

    // SQLSTATE 23000 covers several different failures; the MySQL driver
    // code tells them apart.
    $driverCode = (int)($e->errorInfo[1] ?? 0);
    if ($driverCode === 1062) {
        // Duplicate key: two concurrent registrations raced past the SELECT checks.
        respond_error(409, 'This device or credential is already registered');
    }
    respond_error(500, 'Could not save credential' . $e->getMessage());
} catch (Throwable $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('register_verify persist error: ' . $e->getMessage());
    respond_error(500, 'Could not save credential' . $e->getMessage());
}