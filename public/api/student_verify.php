<?php
declare(strict_types=1);

/**
 * GET /api/student_verify.php?studentId=...&deviceId=...
 *
 * The student's identity comes from the authenticated session ($ses).
 * This endpoint checks that the posted matric number belongs to a real
 * student record, that the posted device matches the one on file, and
 * that a passkey is already registered — before the sign-in page lets
 * the student proceed to the geofence + passkey steps.
 */

header('Content-Type: application/json');

/* Session Manager file */
require_once __DIR__ . '../../config/sessionManager.php';
require_once __DIR__ . '../../config/config.php';

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

function str_field(array $src, string $key): string
{
    $v = $src[$key] ?? '';
    return (is_string($v) || is_int($v)) ? trim((string)$v) : '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

/* This is a GET request (student-helpers.js sends a query string, not a
 * JSON body) — was reading php://input, which is always empty here and
 * made every call fail with "Invalid request body". */
$postedStudentId = str_field($_GET, 'studentId');
$postedDeviceId = str_field($_GET, 'deviceId');

if ($postedStudentId === '') {
    /* Submitted details are missing/invalid. Return error. */
	respond_error(403, 'Matric No is required.');
}

if (!preg_match('/^(202[0-6])\d{7}$/', $postedStudentId)) {
    /* Submitted details are missing/invalid. Return error. */
	respond_error(403, 'Matric Number must be exactly 11 digits and begin with 2020–2026!');
}

if ($postedDeviceId === '') {
    /* Submitted details are missing. Return error */
	respond_error(403, 'Device not recognized.');
}

try {
	$postedStudentIdHex = strtoupper(dechex((int)$postedStudentId));

	// Obtain Student's details from 'studentsDetails' table
	$sql =
		"SELECT userID, userEmil, deviceID
		FROM studentsDetails
		WHERE userID = :userID
		LIMIT 1";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(':userID', $postedStudentIdHex, PDO::PARAM_STR);

	$stmt->execute();

	$student = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$student) {
		respond_error(404, 'Student record not found');
	}

	// Was reading BOTH of these off $studentID['deviceID'] — the student's
	// own hex ID has to come from the userID column, not deviceID.
	$studentIdHex = trim((string)($student['userID'] ?? ''));
	$studentDevice = trim((string)($student['deviceID'] ?? ''));
	$studentEmail = trim((string)($student['userEmil'] ?? ''));

	if ($studentDevice === '' || !hash_equals($studentDevice, $postedDeviceId)) {
		respond_error(403, 'This is not the device registered to your account');
	}

	$ses->set('usrID', $studentIdHex);
	$ses->set('usrEmaile', $studentEmail);
	$ses->set('studentDevID', $studentDevice);

	$credSql =
		"SELECT userID
		FROM student_passkey_credentials
		WHERE userID = :userID
		LIMIT 1";

	$credStmt = $pdo->prepare($credSql);
	$credStmt->bindParam(':userID', $postedStudentIdHex, PDO::PARAM_STR);

	$credStmt->execute();

	$cred = $credStmt->fetch(PDO::FETCH_ASSOC);

	if (!$cred) {
		respond_error(404, 'No registered passkey for this account');
	}

	respond_success([
		// Decimal matric number, exactly as posted — this is what
		// signin_options.php / signin_verify.php expect back as "studentId".
		// (Was $studentUserID, an undefined variable, before.)
		'StudentID'    => $postedStudentId,
		'deviceID'     => $studentDevice,
		'StudentEmail' => $studentEmail,
	], 'Student verified.');

} catch (Throwable $e) {
    error_log('student_verify error: ' . $e->getMessage());
    respond_error(500, 'Could not verify student: ' . $e->getMessage());
}