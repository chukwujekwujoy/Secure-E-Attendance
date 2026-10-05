<?php
declare(strict_types=1);

/**
 * create.php
 * This script is used to create attendance sessions
 * It is called from the lecturer dashboard page (dashboard.php)
 * 
 * Inserts a new session into:
 * 1. qr_sessions  (session_Id, course_code, tutor_id, is_active)
 * 2. attendance_logs  (session_id, course_code, tutor_id, total_signins, eraValid)
 * 
 * Both inserts happen inside a single transaction so the two tables
 * never end up out of sync with each other.
 */


header('Content-Type: application/json');

/* Database file */
require_once __DIR__ . '../../config/dbPlay.php';
/* Session Manager file */
require_once __DIR__ . '../../config/sessionManager.php';
/*  Manager file */
require_once __DIR__ . '../../config/geo.php';


/* Initialize session manager */
use SessionManager\SessionManager;

/* 30 minutes lifetime */
$ses = new SessionManager(3600);
$respondTime = new DateTimeImmutable('now', new DateTimeZone('+01:00'));
$dated = $respondTime->format('Y-m-d H:i:s');

function respond(int $httpCode, array $payload): void {
	http_response_code($httpCode);
	echo json_encode($payload);
	exit;
}

/* Only POST method is allowed */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	respond(405, [
		'success' => false,
		'error' => 'Method not allowed',
		'current_timestamp' => $dated
	]);
}


/* Parsing JSON body */
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
	respond(400, [
		'success' => false,
		'error' => 'Invalid or missing JSON body',
		'current_timestamp' => $dated
	]);
}

$sessionId = trim((string)($data['session_id'] ?? ''));
$courseCode = trim((string)($data['course_code'] ?? ''));
$tutorId = trim((string)($data['tutor_id'] ?? ''));

/* Geolocation Detection */
$geocoords = $data['locationData'] ?? null;
$lat = $data['locationData']['lat'] ?? '';
$longi = $data['locationData']['lng'] ?? '';

/* IP Detection */
$ip_address = '';
$ipp = $_SERVER['REMOTE_ADDR'];
$ipp2 = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
$trustedProxies = ['127.0.0.1', '::1']; // add your proxy IPs here
if (in_array($ipp, $trustedProxies, true) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	// X-Forwarded-For: client, proxy1, proxy2 -> take the first
	$forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	$candidate = trim($forwarded[0]);
	if (filter_var($candidate, FILTER_VALIDATE_IP)) {
		$ip_address = $candidate;
	}
}

if (empty($ip_address) && filter_var($ipp, FILTER_VALIDATE_IP)) {
    $ip_address = $ipp;
}

if (!isValidCoordinate($lat, $longi)) {
	respond(400, [
		'success' => false,
		'error' => 'Invalid coordinates',
		'current_timestamp' => $dated
	]);
}

if ($sessionId === '' || $courseCode === '' || $tutorId === '' || $lat === '' || $longi === '') {
	respond(400, [
		'success' => false,
		'error' => 'session_id, course_code, tutor_id, lattitude, longitude parameters are required',
		'current_timestamp' => $dated
	]);
}

$eraValid = time();

/* Inserting into qr_sessions and attendance_logs, atomically */
try {
	$pdo->beginTransaction();
	
	$qrStmt = $pdo->prepare(
		'INSERT INTO qr_sessions (session_Id, course_code, tutor_id, is_active)
		VALUES (:session_id, :course_code, :tutor_id, :is_active)'
	);

	$qrStmt->execute([
		':session_id' => $sessionId,
		':course_code' => $courseCode,
		':tutor_id' => $tutorId,
		':is_active' => 1,
	]);
	
	$logStmt = $pdo->prepare(
		'INSERT INTO attendance_logs (session_id, course_code, tutor_id, total_signins, signins, eraValid)
		VALUES (:session_id, :course_code, :tutor_id, :total_signins, :signins, :eraValid)'
	);
	
	$logStmt->execute([
		':session_id' => $sessionId,
		':course_code' => $courseCode,
		':tutor_id' => $tutorId,
		':total_signins' => 0,
		':signins' => json_encode([]),
		':eraValid' => $eraValid,
	]);
	
	$otherStmt = $pdo->prepare(
		'INSERT INTO otherLogs (session_id, tutor_id, ip_address, geocoordinates)
		VALUES (:session_id, :tutor_id, :ip_address, :geocoords)'
	);
	$otherStmt->execute([
		':session_id' => $sessionId,
		':tutor_id' => $tutorId,
		':ip_address' => $ip_address,
		':geocoords' => $geocoords !== null ? json_encode($geocoords) : null,
	]);
	
	$pdo->commit();
	
	
	
} catch (PDOException $e) {
	
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	
	/** 
	 * In a scenario where session id 
	 * already exists, return a 409 error
	*/
	if ((int)$e->getCode() === 23000) {
		respond(409, [
			'success' => false,
			'error' => 'session_id already exists. Kindly generate a new session id',
			'current_timestamp' => $dated
		]);
	}
	
	$inserr = htmlspecialchars($e->getMessage());
	respond(500, [
		'success' => false,
		'error' => 'Database error: ' . $inserr,
		'current_timestamp' => $dated
	]);
	
	/* TODO: Do not allow DB internal errors be visible to the client */
	/** 
	 * error_log('create.php DB error: ' . $e->getMessage());
	 *  respond(500, ['success' => false, 'error' => 'Database error']);
	*/
	
}

/* Success response */
respond(201, [
	'success' => true,
	'session_id' => $sessionId,
	'course_code' => $courseCode,
	'tutor_id' => $tutorId,
	'ip_address' => $ip_address,
	'geolog' => $geocoords,
	'is_active' => true,
	'total_signins' => 0,
	'nonce' => $eraValid,
	'current_timestamp' => $dated
]);

?>