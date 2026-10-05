<?php
declare(strict_types=1);

/**
 * POST /api/register_options.php
 * Body: { "studentId": "...", "email": "...", "deviceId": "..." }
 *
 * The student's identity comes from the authenticated session ($ses); the
 * posted studentId/email must agree with it. The device is checked against
 * studentsDetails.deviceID, then PublicKeyCredentialCreationOptions JSON is
 * returned for navigator.credentials.create(). The raw challenge and the
 * pending registration context are stored in the session so
 * register_verify.php can tie the credential back to this exact student
 * and device.
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

function respond_error(int $status, string $message): void
{
    http_response_code($status);
    // register-passkey.js reads "message"; "error" is kept for other callers.
    echo json_encode([
		'success' => false,
		'error' => $message,
		'message' => $message,
		'current_timestamp' => current_dated()
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
$studentMail = $ses->get('usrEmaile');
$studentDevice = $ses->get('studentDevID');
$studentId = hexdec($studentIdHex);

if ($studentId === null || $studentId === "") {
    /* Session details are missing/invalid. Destroy it; the student must start again. */
    $ses->destroy();
    respond_error(401, 'Invalid Student session ID. Please start again.');
}
if ($studentMail === null || $studentMail === '') {
    /* Session details are missing/invalid. Destroy it; the student must start again. */
    $ses->destroy();
    respond_error(401, 'Invalid Student session mail. Please start again.');
}
if ($studentDevice === null || $studentDevice === '') {
    /* Session details are missing/invalid. Destroy it; the student must start again. */
    $ses->destroy();
    respond_error(401, 'Invalid Student session device. Please start again.');
}

/* Data posted by register-passkey.js */
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    respond_error(400, 'Invalid request body');
}

$postedStudentId = trim((string)($body['studentId'] ?? ''));
$email = trim((string)($body['email'] ?? ''));
$deviceId = trim((string)($body['deviceId'] ?? ''));

if (!$deviceId) {
	$ses->destroy();
	respond_error(403, 'Device details parameters are required.');
}

if (!$email) {
	$ses->destroy();
	respond_error(403, 'Student email parameters are required.');
}

if (!$postedStudentId) {
	$ses->destroy();
	respond_error(403, 'Student ID parameters are required.');
}

if ($studentDevice !== $deviceId) {
	$ses->destroy();
	respond_error(403, 'Device details do not match your session. Please start again.');
}

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


try {

    $stmt = $pdo->prepare('SELECT userID, userEmil, deviceID FROM studentsDetails WHERE userID = ?');
    $stmt->execute([$studentIdHex]);
    $student = $stmt->fetch();

    if (!$student) {
        respond_error(404, 'Student record not found');
    }
    if (strcasecmp(trim((string)$student['userEmil']), $studentMail) !== 0) {
        respond_error(403, 'Your account email does not match our records');
    }

    // The device must be the one already bound to this student, if any.
    $boundDeviceId = trim((string)($student['deviceID'] ?? ''));
    if ($boundDeviceId !== '' && !hash_equals($boundDeviceId, $deviceId)) {
        respond_error(403, 'This is not the device registered to your account');
    }

    // ...and it must not belong to a different student.
    $stmt = $pdo->prepare('SELECT 1 FROM studentsDetails WHERE deviceID = ? AND userID <> ? LIMIT 1');
    $stmt->execute([$deviceId, $studentIdHex]);
    if ($stmt->fetch()) {
        respond_error(409, 'This device is already linked to another student');
    }

    // Credentials this student already has, so the authenticator refuses
    // to create a duplicate (surfaces client-side as InvalidStateError).
    $excludeCredentialIds = [];
    $stmt = $pdo->prepare('SELECT credential_id FROM student_passkey_credentials WHERE userID = ?');
    $stmt->execute([$studentIdHex]);
    foreach ($stmt->fetchAll() as $row) {
        $excludeCredentialIds[] = b64url_decode($row['credential_id']);
    }

    // Credentials this student already has, so the authenticator refuses
    // to create a duplicate (surfaces client-side as InvalidStateError).
    // $stmt = $pdo->prepare('SELECT 1 FROM student_passkey_credentials WHERE userID = ? LIMIT 1');
    // $stmt->execute([$studentIdHex]);
    // if ($stmt->fetch()) { respond_error(409, 'A passkey is already registered for this account'); }

    // No user_handle column exists, so derive a stable, non-PII handle from
    // the student ID. Same student => same handle on every registration.
    $userHandle = hash('sha256', 'passkey-user-handle:' . $studentId, true);

    $options = WebAuthn::buildRegistrationOptions(
        $studentIdHex,  // user.name
        $userHandle,    // user.id
        $studentMail,   // user.displayName
        $excludeCredentialIds
    );

    // Persist challenge + pending registration state server-side only.
    $ses->set('webauthn_reg', [
        'student_id'  => $studentIdHex,
        'email'       => $studentMail,
        'device_id'   => $deviceId,
        'user_handle' => b64url_encode($userHandle),
        'challenge'   => b64url_encode($options['_challengeRaw']),
        'expires_at'  => time() + CHALLENGE_TTL,
    ]);

    unset($options['_challengeRaw']); // never send raw challenge bytes twice
    echo json_encode($options);
} catch (Throwable $e) {
    error_log('register_options error: ' . $e->getMessage());
	$sendErr = $e->getMessage();
    respond_error(500, 'Could not start registration: ' . $sendErr);
}