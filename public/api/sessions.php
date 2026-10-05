<?php
declare(strict_types=1);

/**
 * session.php
 * Looks up ONE session by session_id (globally unique, it's what the QR carries).
 * Used for the "does this session already exist?" check and for fetching a
 * single session's roster.
 *
 * GET ?session_id=XXXX[&tutor_id=YYY]
 * - exists: always returned
 * - session (with roster): only returned when tutor_id matches the owner
 */

header('Content-Type: application/json');

/* Database file */
require_once __DIR__ . '../../config/dbPlay.php';
/* Session Manager file */
require_once __DIR__ . '../../config/sessionManager.php';

/* Initialize session manager */
use SessionManager\SessionManager;

/* 30 minutes lifetime */
$ses = new SessionManager(3600);

function respond(int $httpCode, array $payload): void {
	http_response_code($httpCode);
	echo json_encode($payload);
	exit;
}

function current_dated(): string
{
	return (new DateTimeImmutable('now', new DateTimeZone('+01:00')))->format('Y-m-d H:i:s');
}


/* Only GET method is allowed */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	respond(405, [
		'success' => false,
		'error' => 'Method not allowed',
		'current_timestamp' => current_dated()
	]);
}


/* Reading query parameters */
$sessionId = strtoupper(trim((string)($_GET['session_id'] ?? '')));
$tutorId   = trim((string)($_GET['tutor_id'] ?? ''));

if ($sessionId === '') {
	respond(400, [
		'success' => false,
		'error' => 'session_id parameter is required',
		'current_timestamp' => current_dated()
	]);
}

/* Looking up matching sessions in attendance_logs */
try {
	$stmt = $pdo->prepare(
		'SELECT session_id, course_code, tutor_id, signins, total_signins, eraValid
		FROM attendance_logs
		WHERE session_id = :session_id
		LIMIT 1'
	);
	$stmt->execute([
		':session_id' => $sessionId
	]);
	
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	
} catch (PDOException $e) {
	error_log('session.php DB error: ' . $e->getMessage());
	
	$test_err = $e->getMessage();
	
	respond(500, [
		'success' => false,
		'error' => 'Database error',
		'current_timestamp' => current_dated()
	]);
}

/* Not found is a normal answer here, not an error: 200 + exists:false,
   so the client's api() helper (which throws on non-2xx) doesn't blow up. */
if (!$row) {
	respond(200, [
		'success' => true,
		'exists' => false,
		'owned' => false,
		'session' => null,
		'current_timestamp' => current_dated()
	]);
}

$owned = ($tutorId !== '' && $tutorId === (string)$row['tutor_id']);

if ($owned) {
	$elapsed = time() - (int)$row['eraValid'];
	$row['time_status'] = ($elapsed >= 0 && $elapsed <= 600) ? 'active' : 'expired';
}

respond(200, [
	'success' => true,
	'exists'  => true,
	'owned'   => $owned,
	'session' => $owned ? $row : null,
	'current_timestamp' => current_dated()
]);