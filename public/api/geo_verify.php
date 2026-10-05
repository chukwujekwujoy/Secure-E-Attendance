<?php
declare(strict_types=1);

/**
 * GET /api/geo_verify.php?attendID=...&lat=...&lng=...&radius=...
 *
 * Query params (sent by attendance.js / student-helpers.js):
 *   attendID  string  the attendance session the student is checking into
 *   lat       number  student's reported latitude
 *   lng       number  student's reported longitude
 *   radius    number  student's GPS accuracy radius (navigator.geolocation's
 *                     coords.accuracy), in meters — NOT the geofence radius.
 *
 * The geofence itself (tutor's lat/lng/radius_m) is looked up fresh from
 * otherLogs.geocoordinates for this session — never trusted from the
 * client, and not cached in the PHP session either, since the tutor may
 * still be able to adjust it after the session starts.
 * What the client sends as "radius" is treated as its
 * GPS accuracy and only ever widens the allowed circle by a clamped amount
 * (see clampAccuracyBuffer() in geo.php).
 */

header('Content-Type: application/json');

require_once __DIR__ . '../../config/config.php';
require_once __DIR__ . '../../config/geo.php';
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

function is_missing($v): bool
{
	return $v === null || (is_string($v) && trim($v) === '');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	respond_error(405, 'Method not allowed');
}

/* Initialize + start session */
$ses = new SessionManager(600);
$ses->start();

/**
 * Session Auth
 *
 * This endpoint expects a session ID that is already active, with its
 * details stored in $ses (set by students-signin.php).
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

/* Authenticated attendance-session context, straight from the session.
 * NOTE: the original file also checked a $studentIdHex here, but this
 * session never stores one (only student_verify.php's session does) —
 * that check always failed and destroyed the session on every request.
 * Removed; this endpoint only needs the attendance-session context. */
$attendID = $ses->get('attendID');
$course_code = $ses->get('course_code');
$tutor_id = $ses->get('tutor_id');
// Values seen when the attendance session started — only used for the
// "is anything missing" sanity check below. The actual geofence used to
// decide within/outside is re-fetched fresh further down, since the
// tutor can still adjust it after the session starts.
$radius = $ses->get('radius');
$tutorLat = $ses->get('tutorLat');
$tutorLong = $ses->get('tutorLong');

if (is_missing($attendID) || is_missing($course_code) || is_missing($tutor_id) || is_missing($radius) || is_missing($tutorLat) || is_missing($tutorLong)) {
	/* Session details are missing/invalid. Destroy it; the student must start again. */
	$ses->destroy();
	respond_error(401, 'Invalid session details. Please start again.');
}

/* This is a GET request — read from the query string, not a JSON body */
$postedAttendId = trim((string)($_GET['attendID'] ?? ''));
$rawLat = $_GET['lat'] ?? null;
$rawLng = $_GET['lng'] ?? null;
$rawAccuracy = $_GET['radius'] ?? null; // GPS accuracy radius, not the geofence radius

if ($postedAttendId === '' || !is_numeric($rawLat) || !is_numeric($rawLng)) {
	/* Submitted details are missing/invalid. */
	respond_error(400, 'Attendance ID, latitude and longitude are required.');
}

if ($postedAttendId !== $attendID) {
	/* Session details are missing/invalid. Destroy it; the student must start again. */
	$ses->destroy();
	respond_error(401, 'Details do not match your session. Please start again.');
}

$studentLat = (float) $rawLat;
$studentLng = (float) $rawLng;

if (!isValidCoordinate($studentLat, $studentLng)) {
	respond_error(400, 'Latitude/longitude out of range.');
}

/* Looking up the matching session's geofence in otherLogs */
try {
	$geoSql = "SELECT geocoordinates FROM otherLogs WHERE session_id = :attendID LIMIT 1";
	$stmt = $pdo->prepare($geoSql);
	$stmt->bindParam(':attendID', $attendID, PDO::PARAM_STR);
	$stmt->execute();

	// Was fetchAll() indexed like a single row (['geocoordinates']) — fetchAll()
	// returns a list of rows, so that always looked up a non-existent key.
	// This session_id is unique, so fetch() a single row instead.
	$geoSession = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$geoSession) {
		respond_error(500, 'Session geocoordinates not found.');
	}

	$geoCoordinates = json_decode((string)$geoSession['geocoordinates'], true);

	if (!is_array($geoCoordinates) || json_last_error() !== JSON_ERROR_NONE) {
		respond_error(500, 'Invalid geocoordinates data.');
	}

	// NOTE: students-signin.php reads this same otherLogs.geocoordinates blob
	// using the key 'radius_m' — kept consistent with that here. If your
	// actual column is named 'radius' instead, change both places together.
	if (!isset($geoCoordinates['lat'], $geoCoordinates['lng'], $geoCoordinates['radius_m'])) {
		respond_error(500, 'Incomplete geocoordinates data.');
	}

	$tutorLat = (float) $geoCoordinates['lat'];
	$tutorLong = (float) $geoCoordinates['lng'];
	$geofenceRadiusM = (float) $geoCoordinates['radius_m'];

	if (!isValidCoordinate($tutorLat, $tutorLong) || $geofenceRadiusM <= 0) {
		/* The session's own geofence data is bad — nothing the student can fix here. */
		$ses->destroy();
		respond_error(500, 'Attendance session has an invalid geofence. Please contact your lecturer.');
	}

	/* GPS accuracy buffer — clamped so a spoofed huge value can't widen the geofence */
	$accuracyBufferM = clampAccuracyBuffer($rawAccuracy);
	$effectiveRadiusM = $geofenceRadiusM + $accuracyBufferM;

	$distanceM = haversineDistanceMeters($tutorLat, $tutorLong, $studentLat, $studentLng);
	$withinRadius = $distanceM <= $effectiveRadiusM;

	if (!$withinRadius) {
		$moveCloserM = round($distanceM - $effectiveRadiusM, 1);
		// respond_error() now takes an optional $extra array, so this
		// distance/radius detail actually reaches the client instead of
		// being silently dropped.
		respond_error(
			403,
			sprintf(
				'You are %.0fm from the class location — move %.0fm closer to sign in.',
				$distanceM,
				$moveCloserM
			),
			[
				'distance_meters' => round($distanceM, 1),
				'allowed_radius_meters' => round($effectiveRadiusM, 1),
				'move_closer_meters' => $moveCloserM,
			]
		);
	}
} catch (PDOException $e) {
	respond_error(500, 'DB error: ' . $e->getMessage());
} catch (RuntimeException $e) {
	respond_error(500, 'RuntimeException error: ' . $e->getMessage());
} catch (Throwable $e) {
	respond_error(500, 'Something went wrong: ' . $e->getMessage());
}

/* Success response — was calling the undefined respond() function */
respond_success([
	'within_radius' => true,
	'distance_meters' => round($distanceM, 1),
], "You're within the attendance area.");