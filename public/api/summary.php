<?php
declare(strict_types=1);

/**
 * summary.php
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
$courseCode = trim((string)($_GET['course_code'] ?? ''));
$tutorId = trim((string)($_GET['tutor_id'] ?? ''));

if ($courseCode === '' || $tutorId === '') {
	respond(400, [
		'success' => false,
		'error' => 'course_code and tutor_id parameters are required',
		'current_timestamp' => current_dated()
	]);
}

/* Looking up matching sessions in attendance_logs */
try {
	$stmt = $pdo->prepare(
		'SELECT session_id, course_code, tutor_id, signins, total_signins, eraValid
		FROM attendance_logs
		WHERE course_code = :course_code AND tutor_id = :tutor_id'
	);
	
	$stmt->execute([
		':course_code' => $courseCode,
		':tutor_id' => $tutorId,
	]);
	
	$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	/* Check session validity */
	$currentTime = time();
	
	
	foreach ($sessions as &$session) {
		$eraValid = (int)$session['eraValid'];
		$elapsed = $currentTime - $eraValid;
		
		$session['time_status'] = 
			($elapsed >= 0 && $elapsed <= 600)
				? 'active'
				: 'expired';
	}
	
	unset($session);
	
} catch (PDOException $e) {
	
	error_log('summary.php DB error: ' . $e->getMessage());
	
	$test_err = $e->getMessage();
	
	respond(500, [
		'success' => false,
		'error' => 'Database error: ' . $test_err,
		'current_timestamp' => current_dated()
	]);
}

/* Success response */
respond(200, [
	'success' => true,
	'current_timestamp' => current_dated(),
	'course_code' => $courseCode,
	'tutor_id' => $tutorId,
	'count' => count($sessions),
	'sessions' => $sessions
]);

?>