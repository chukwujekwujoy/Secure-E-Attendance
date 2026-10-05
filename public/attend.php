<?php

/* Database file */
require_once __DIR__ . '/config/dbPlay.php';
/* Session Manager file */
require_once __DIR__ . '/config/sessionManager.php';

/* Initialize session manager */
use SessionManager\SessionManager;

$attendID = $course_code = $tutor_id = $eraValid = $created_at = "";
$geoCoordinates = $lat = $lng = $radius = "";
$now = $timedif = "";
$remainderTime = 0;
$err_msg = $err_msg0 = $err_msg1 = $err_msg2 = $err_msg3 = $err_msg4 = $err_msg5 = $err_msg6 = $err_msg7 = "";

// Validate and sanitize attendance input from URL parameters
$attendanceID = isset($_GET['attendance_id']) ? trim($_GET['attendance_id']) : "";
$jsSnippet = $jsScript = "";

try {
	
	// Check if attendance ID exists in 'attendance_logs' table
	$sql = "SELECT session_id, course_code, tutor_id, eraValid FROM attendance_logs WHERE session_id = :attendanceID";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(':attendanceID', $attendanceID, PDO::PARAM_STR);
	$stmt->execute();
	
	$attendData = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if ($attendData !== false) {
		
		$attendID = $attendData["session_id"];
		$course_code = $attendData["course_code"];
		$tutor_id = $attendData["tutor_id"];
		$eraValid = $attendData["eraValid"];
		
		$now = time();
		$timedif = $now - $eraValid;
		if($timedif <= 598){
			$remainderTime = 598 - $timedif;
			// Obtain Lecturer's geocoordinates from 'otherLogs' table
			$geoSql = "SELECT session_id, geocoordinates FROM otherLogs WHERE session_id = :sessionID LIMIT 1";
			$geoStmt = $pdo->prepare($geoSql);
			$geoStmt->bindParam(':sessionID', $attendID, PDO::PARAM_STR);
			$geoStmt->execute();
			
			$geoCoords = $geoStmt->fetch(PDO::FETCH_ASSOC);
			
			if (!$geoCoords) {
				throw new RuntimeException('Session geocoordinates not found.');
			}
			
			$geoCoordinates = json_decode($geoCoords['geocoordinates'], true);
			
			if (!is_array($geoCoordinates) || json_last_error() !== JSON_ERROR_NONE) {
				throw new RuntimeException('Invalid geocoordinates data.');
			}
			
			if (!isset($geoCoordinates['lat']) || !isset($geoCoordinates['lng']) || !isset($geoCoordinates['radius_m'])) {
				throw new RuntimeException('Incomplete geocoordinates data.');
			}
			
			$lat = (float) $geoCoordinates['lat'];
			$lng = (float) $geoCoordinates['lng'];
			$radius = (float) $geoCoordinates['radius_m'];
			
			/* x-minutes lifetime */
			$ses = new SessionManager(600);
			/* Start Session */
			$ses->Start();
			/* Set session variables */
			$ses->Set("attendID", $attendID);
			$ses->Set("course_code", $course_code);
			$ses->Set("tutor_id", $tutor_id);
			$ses->Set("radius", $radius);
			$ses->Set("tutorLat", $lat);
			$ses->Set("tutorLong", $lng);
			
			/* Set session authentication = true */
			$ses->set('authenticated', true);
			/* Bind To Client */
			$ses->bindToClient();
			
		} else{
			// Session is already deactivated
			$err_msg2 = "Oops! Unfortunately this Attendance Session ended ". $timedif . "seconds ago. You will be redirected to origin page to scan another";
			
			$jsSnippet = <<<EOD
			<script type="text/javascript">
			  alert("$err_msg2");
			  window.location.href = "./index.php";
			</script>
			EOD;
		}
		
	} else {
		$err_msg4 = "Invalid Attendance link! Redirecting you for proper QR Code scan.";
		$jsSnippet = <<<EOD
		<script type="text/javascript">
		  alert("$err_msg4");
		  window.location.href = "./index.php";
		</script>
		EOD;
	}
} catch (PDOException $e) {
	$err_msg5 = "Database error: " . htmlspecialchars($e->getMessage());
	$jsScript = <<<EOD
	<script type="text/javascript">
	  alert("$err_msg5");
	</script>
	EOD;
} catch (RuntimeException $e) {
	$err_msg6 = "Runtime error: " . htmlspecialchars($e->getMessage());
	$jsScript = <<<EOD
	<script type="text/javascript">
	  alert("$err_msg6");
	</script>
	EOD;
} catch (Exception $e) {
	$err_msg7 = "An error occurred: " . htmlspecialchars($e->getMessage());
	$jsScript = <<<EOD
	<script type="text/javascript">
	  alert("$err_msg7");
	</script>
	EOD;
}

?><!DOCTYPE html>
<html lang="en">
  <head>
    <!-- SEO Tags -->
	<meta charset="utf-8" />

	<!-- Browser Compatibility Tags -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />

	<!--  Tags -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<!-- Responsive Tags -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

	<!-- Title Tag -->
	<title>Students Attendance Page | FUTO</title>

	<!-- Phosphor Icons CSS File -->
	<link href="/dist/phosphor-icons/duotone/style.css" rel="stylesheet">
	<link href="/dist/phosphor-icons/bold/style.css" rel="stylesheet">

	<!-- Preload Fonts File -->
	<link rel="preload" href="/dist/css/fonts/trebuc.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="/dist/css/fonts/trebuc.woff" as="font" type="font/woff" crossorigin>
	<link rel="preload" href="/dist/css/fonts/Alef-Regular.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="/dist/css/fonts/Alef-Regular.woff" as="font" type="font/woff" crossorigin>
	<link rel="preload" href="/dist/css/fonts/UbuntuMono-R.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="/dist/css/fonts/UbuntuMono-R.woff" as="font" type="font/woff" crossorigin>

	<!-- External Font CSS  -->
	<link href="/dist/css/fonts.css" rel="stylesheet">

	<!-- External Stylesheet File -->
	<link href="/dist/css/students.css" rel="stylesheet">
	<link href="/dist/css/net-log.css" rel="stylesheet">
	<link href="/dist/css/preloader.css" rel="stylesheet">

	<!-- Read course_code / tutor_id from the URL as soon as possible -->
	<script type="text/javascript">
	  function getDeviceId() {
		  const STORAGE_KEY = "futo_device_id";
		  let id = localStorage.getItem(STORAGE_KEY);
		  
		  return id;
	  }
	  
	  window.deviceId = getDeviceId();
	</script>
	<script src="/dist/js/courses_data.js"></script>
	<!-- Site favicon Tag -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico" />

	<!-- My Manifest Tag (JSON format) -->
	<link rel="manifest" href="./manifest.json" />
  </head>
  <body>

    <!--  -->
	<dialog id="attendDetails" class="modal">
	  <section>
	    <div id="" class="modal-header">
		  <h2 class="modal-sub">CLASS ATTENDANCE DETAILS</h2>
		</div>
		
		<div id="sessionDisplay" class="sessionDisplay">
		  <!-- Class details showing Attendance ID, Course Code, Course Title, Semester, Year -->
		  <div id="sessionDetails" class="sessionDetails"></div>
		  
		  <!-- Timing -->
		  <div>Time remaining: <br /><span id="timingCountdown" class="timingCountdown"></span></div>
		</div>
		
		<!-- Responses from API calls -->
		<div id="network_messages" class="network_messages"></div>
	  </section>
	</dialog>

	<section class="root" id="root" title="Attendance Form">
	  <!-- Default view: Sign In -->
	  <div class="auth-card" id="">
	    <div class="logo-panel">
		  <div class="logo-panel-heading" id="" title="School Logo">
		    <picture>
			  <!-- Dark mode version (shown when student prefers dark) -->
			  <source srcset="/dist/images/FLogo.png" media="(prefers-color-scheme: dark)">

			  <!-- Light mode version (shown when student prefers light) -->
			  <img src="/dist/images/FLogo1.png" alt="School Logo">
			</picture>
		  </div>
		</div><br />

		<!-- Form Element -->
		<form id="signin">

		  <!-- MATRIC NUMBER Element -->
		  <div title="Input Your School Matric No">
		    <label for="matricNo">Matric Number</label>
			<div class="input-container" id="input-container">
			  <input type="tel" id="matricNo" autofocus name="matricNo" placeholder="Student Matric No" required value="" />
			  <i class="ph-bold ph-student"></i>
			</div>
		    <span class="error" id="uRegNoErr"></span><br />
		  </div>

		  <!-- Messages -->
		  <div class="messages">
		    <div id="st-messages"></div>
		  </div><br /><br />

		  <!-- Submit Button Element -->
		  <button type="submit" id="submitForm" title="Submit Attendance">SUBMIT</button>

		</form>
		
	  </div>
	</section>

	<script>
	  // Attendance details is rendered server-side from the session — never taken from user input.
	  const attendID = <?php echo json_encode($attendID); ?>;
	  const course_code = <?php echo json_encode($course_code); ?>;
	  const tutor_id = <?php echo json_encode($tutor_id); ?>;
	  const timedif = <?php echo json_encode($timedif); ?>;
	  const remainderTime = <?php echo json_encode($remainderTime); ?>;
	  const lat = <?php echo json_encode($lat); ?>;
	  const lng = <?php echo json_encode($lng); ?>;
	  const radius = <?php echo json_encode($radius); ?>;
	  const deviceID = window.deviceId;
	  
	  console.log('Student Device ID: ', deviceID);
	  console.log('Attendance ID: ', attendID);
	  console.log('Course Code: ', course_code);
	  console.log('Tutor ID: ', tutor_id);
	  console.log('Time remaining (in seconds): ', timedif);
	  console.log('Tutor Latitude: ', lat);
	  console.log('Tutor Longitude: ', lng);
	  console.log('Attendance Boundary Radius (in metres): ', radius);
	  
	  // Setting PHP endpoints.
	  const API_BASE = '/api';
	  
	  // Display timer for students to see remaining time.
	  
	  (function () {
		  
		  const dialog = document.querySelector('#attendDetails');
		  const detailsEl = document.querySelector('#sessionDetails');
		  const timerEl = document.querySelector('#timingCountdown');
		  const submitBtn = document.querySelector('#submitForm');
		  
		  const cap = s => s.charAt(0).toUpperCase() + s.slice(1);
		  
		  const course = findCourse(course_code); // null if not in the list
		  
		  const rows = [
			  ['Attendance ID', attendID],
			  ['Course Code', course_code],
			  ['Course Title', course ? course.title : 'Unknown course'],
			  ['Semester', course ? cap(course.semester) + ' Semester' : '—'],
			  ['Level', course ? course.year + '00 Level' : '—'],
		  ];
		  
		  // Build with textContent (no innerHTML) so nothing is parsed as HTML.
		  const table = document.createElement('table');
		  const tbody = document.createElement('tbody');
		  
		  for (const [label, value] of rows) {
			  const tr = document.createElement('tr');
			  const tdLabel = document.createElement('td');
			  const tdValue = document.createElement('td');
			  tdLabel.textContent = label;
			  tdValue.textContent = value;
			  
			  tr.append(tdLabel, tdValue);
			  tbody.appendChild(tr);
		  }
		  table.appendChild(tbody);
		  detailsEl.replaceChildren(table);
		  
		  // Countdown, anchored to a deadline so it doesn't drift.
		  const deadline = Date.now() + Number(remainderTime) * 1000;
		  
		  function tick() {
			  const left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
			  const m = String(Math.floor(left / 60)).padStart(2, '0');
			  const s = String(left % 60).padStart(2, '0');
			  timerEl.textContent = `${m}:${s}`;
			  
			  if (left <= 0) {
				  clearInterval(timer);
				  timerEl.textContent = 'Session ended';
				  submitBtn.disabled = true;
				  dialog.close();
			  }
		  }
		  
		  const timer = setInterval(tick, 1000);
		  tick(); // render immediately instead of after 1s
		  
		  
	  })();
	</script>
	<script type="module">
	  import { FormValidator } from '/dist/js/signin.js';
	  
	  const regNoRegex = /^(202[0-6])\d{7}$/;
	  
	  new FormValidator({
		  form: document.querySelector('#signin'),
		  matric: document.querySelector('#matricNo'),
		  submitBtn: document.querySelector('#submitForm'),
		  matricError: document.querySelector('#uRegNoErr'),
		  stMessages: document.querySelector('#st-messages'),
		  inputDiv: document.querySelector('#tellInputDiv'),
		  
		  matricRegex: regNoRegex
	  });
	</script>
	<script>
	  
	  // -----------------------------
	  // Precise geolocation
	  // -----------------------------
	  /**
	   * enableHighAccuracy tells the browser to use its best available
	   * location source (GPS on phones, not just wifi/IP triangulation).
	   * On mobile browsers that expose a separate "precise" vs "approximate"
	   * location permission (iOS Safari, recent Chrome), this is also what
	   * triggers the precise-location prompt.
	   * 
	   */
	  
	  const GEO_MAX_GOOD_ACCURACY_M = 25;   // above this, ask the student to confirm
	  const GEO_ATTENDANCE_RADIUS_M = 300;  // shown in the low-accuracy warning
	  
	  /**
	   * Gets the device's location and reports progress to the #network_messages log.
	   * Resolves to { lat, lng, accuracy_m }, or null if the student can't/won't
	   * share a usable location (the reason has already been logged).
	   * 
	  */
	  
	  function getPreciseLocation() {
		  const statusEl = document.querySelector('#session-status');
		  
		  if (!('geolocation' in navigator)) {
			  setStMessage('Your browser does not support geolocation, so your attendance cannot be verified.', 'error');
			  return Promise.resolve(null);
		  }
		  
		  setStMessage('Getting your location…');
		  
		  return new Promise((resolve) => {
			  navigator.geolocation.getCurrentPosition((position) => {
				  const {
					  latitude,
					  longitude,
					  accuracy
				  } = position.coords;
				  
				  if (accuracy > GEO_MAX_GOOD_ACCURACY_M) {
					  const proceed = confirm(
						  `Your location accuracy is low (±${Math.round(accuracy)}m). ` +
						  `You might be rejected as the attendane radius is set to ${GEO_ATTENDANCE_RADIUS_M}m. Continue anyway?`
					  );
					  if (!proceed) {
						  setStMessage('Location check cancelled. Attendance was not recorded.', 'error');
						  resolve(null);
						  return;
					  }
				  }
				  
				  setStMessage(`Location captured (±${Math.round(accuracy)}m).`, 'success');
				  
				  resolve({
					  lat: latitude,
					  lng: longitude,
					  accuracy_m: accuracy,
				  });
			  },
			  (error) => {
				  // describeGeoError() tells apart denied / unavailable / timed out
				  
				  setStMessage(describeGeoError(error), 'error');
				  resolve(null);
			  },
			  {
				  enableHighAccuracy: true,
				  timeout: 15000,
				  maximumAge: 0
			  }
			  );
		  });
	  }
	  
	</script>
	<script src="/dist/js/student-helpers.js"></script>
	<script src="/dist/js/net-log.js"></script>
	<script src="/dist/js/attendance.js"></script>
	<?php echo $jsSnippet;?>
	<?php echo $jsScript;?>
  </body>
</html>
