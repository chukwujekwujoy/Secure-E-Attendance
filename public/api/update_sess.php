<?php
declare(strict_types=1);

/**
 * update_sess.php
 * This script returns all session_id(s) for a given course_code and tutor_id
 * It is called from the lecturer dashboard page (dashboard.php)
 * 
 * Reads from:
 * 1. qr_sessions  (session_Id, course_code, tutor_id, is_active)
 * 
 * 
 */


header('Content-Type: application/json');

/* Database file */
require_once __DIR__ . '../../config/dbPlay.php';
/* Session Manager file */
require_once __DIR__ . '../../config/sessionManager.php';


/* Initialize session manager */
use SessionManager\SessionManager;

/* Initialize + start session */
$ses = new SessionManager(3600);
$ses->start();

function current_dated(): string
{
	return (new DateTimeImmutable('now', new DateTimeZone('+01:00')))->format('Y-m-d H:i:s');
}

function respond_error(int $status, string $message, array $extra = []): void
{
	http_response_code($status);
	// attendance.js reads "message"; "error" is kept for other callers.
	echo json_encode(array_merge([
		'success' => false,
		'error' => $message,
		'message' => $message,
		'current_timestamp' => current_dated(),
	], $extra));
	exit;
}

function respond_success(array $data, string $message = ''): void
{
	http_response_code(200);
	echo json_encode(array_merge([
		'success' => true,
		'message' => $message,
		'current_timestamp' => current_dated(),
	], $data));
	exit;
}

/* Only PATCH method is allowed */
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
	respond_error(405, 'Method not allowed');
}

function is_missing($v): bool
{
	return $v === null || (is_string($v) && trim($v) === '');
}


/**
 * Session Auth
 *
 * This endpoint expects a tutor ID that is already active, with its
 * details stored in $ses
 * (set by 'auth/signin.php').
 *
 */

/* Check if session has expired */
if ($ses->isExpired()) {
	respond_error(401, 'Session expired. Please start again.');
}

/* Verify the client fingerprint (destroys the session on mismatch) */
$ses->bindToClient();

/* Check if session is authenticated */
if ($ses->get('authenticated') !== true) {
	respond_error(401, 'Invalid session. Please start again.');
}

$tutorId = $ses->get('usrID');
$tutorMail = $ses->get('usrEmaile');

if ($tutorId === null || $tutorMail === null) {
	/* User information is empty. */
	respond_error(401, 'Client is forbidden.');
}


/* Reading query parameters */
$body = json_decode(file_get_contents('php://input'), true);

$sessionId = $body['session_Id'] ?? null;

if (!$sessionId) {
	respond_error(400, 'Session ID parameter is required');
}

try {
	/* Nothing changed: either not found/not yours, or already inactive */
	$check = $pdo->prepare(
		"SELECT is_active FROM qr_sessions
		WHERE session_Id = :session_id
		AND tutor_id = :tutor_id
		LIMIT 1"
	);
	$check->execute([':session_id' => $sessionId, ':tutor_id' => $tutorId]);
	$row = $check->fetch(PDO::FETCH_ASSOC);
	
	if (!$row) {
		respond_error(404, 'Session not found.');
	}
	
	if($row['is_active'] === 'expired') {
		/* Idempotent: already deactivated */
		respond_success(['session_Id' => $sessionId, 'is_active' => 0], 'Session is already deactivated.');
	}
	
	
	/* Atomic: only deactivates a session this tutor owns that is still active */
	$stmt = $pdo->prepare(
		"UPDATE qr_sessions 
		SET is_active = 'expired' 
		WHERE session_Id = :session_id
		AND tutor_id = :tutor_id
		AND is_active = 'active'"
	);
	
	$stmt->execute([
		':session_id' => $sessionId,
		':tutor_id'   => $tutorId,
	]);
	
	if ($stmt->rowCount() > 0) {
		respond_success(['session_Id' => $sessionId, 'is_active' => 0], 'Session deactivated.');
	}
	
	/* Lost a race: deactivated between the check and the update */
    respond_success(['session_Id' => $sessionId, 'is_active' => 0], 'Session is already deactivated.');
	
} catch (PDOException $e) {
	respond_error(500, 'DB error: ' . $e->getMessage());
} catch (Throwable $e) {
	respond_error(500, 'Something went wrong: ' . $e->getMessage());
}








