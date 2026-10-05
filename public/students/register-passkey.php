<?php

/* Database file */
require_once('../config/dbPlay.php');
/* Session Manager file */
require_once('../config/sessionManager.php');

/* Initialize session manager */
use SessionManager\SessionManager;

$ses = new SessionManager(1800);

/* Start Session */
$ses->Start();


/**
 * Student Auth
 * 
 * This page expects a student to already be registered in (via the register flow),
 * with the student's identity stored in $ses.
 */

/* Check if session has expired */
if ($ses->isExpired()) {
	/* Session expired, user needs to login again */
    header('Location: /students/register.php');
    exit;
}

/* Check if session is authenticated */
if ($ses->get('authenticated') !== true) {
	/* Session authentication check failed, user needs to login again */
    header('Location: /students/register.php');
    exit;
}

/* Verify the client fingerprint */
$ses->bindToClient();


/*
 * Retrieve authenticated user information
 * from the session.
 */
 
/* Get Student ID from authenticated session */
$studentIdHex = $ses->get('usrID');
$studentId = hexdec($studentIdHex);

/* Get Student email address from authenticated session */
$studentMail = $ses->get('usrEmaile');

/* Get Student email address from authenticated session */
$studentDevice = $ses->get('studentDevID');

if (!$studentIdHex || !$studentMail) {
	/* User information is empty. Destroy session and user must signin again */
	$ses->destroy();
	header('Location: /students/register.php');
    exit;
}


$initials = 'S';
?>
<!DOCTYPE html>
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
	<title>Students Passkey Registration Portal | FUTO</title>
	
	<!-- Phosphor Icons CSS File -->
	<link href="/dist/phosphor-icons/duotone/style.css" rel="stylesheet">
	<link href="/dist/phosphor-icons/bold/style.css" rel="stylesheet">
	
	<!-- Font-Awesome Icons CSS File -->
	<link href="/dist/font-awesome@6_4_0/css/all.min.css" rel="stylesheet">
	
	<!-- Peload Fonts File -->
	<link rel="preload" href="/dist/css/fonts/trebuc.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="/dist/css/fonts/trebuc.woff" as="font" type="font/woff" crossorigin>
	<link rel="preload" href="/dist/css/fonts/Alef-Regular.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="/dist/css/fonts/Alef-Regular.woff" as="font" type="font/woff" crossorigin>
	<link rel="preload" href="/dist/css/fonts/DS-DIGIB.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="/dist/css/fonts/DS-DIGIB.woff" as="font" type="font/woff" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
	
	<!-- External Font CSS  -->
	<link rel="preload" href="/dist/css/fonts.css" as="stylesheet">
	
	<!-- External Stylesheet File -->
	<link href="/dist/css/main.css" rel="stylesheet">
	<link href="" rel="stylesheet">
	
	<!-- QRCode Javascript File -->
	<script src="/dist/js/qrcode@1_5_1/qrcode.min.js"></script>
	
	<!-- Javascript snippet to get the device ID from localStorage -->
	<script>
	  function getDeviceId() {
		  const STORAGE_KEY = "futo_device_id";
		  let id = localStorage.getItem(STORAGE_KEY);
		  
		  return id;
	  }
	  
	  window.deviceId = getDeviceId();
	</script>
	
	<!-- Site favicon Tag -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico" />
	
	<!-- My Manifest Tag (JSON format) -->
	<link rel="manifest" href="./manifest.json" />
	
  </head>
  <body>
    
	<!--  -->
	<dialog id="qrDialog">
	  <section>
	    
	  </section>

	  <div id="qrError" class="error"></div>
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
		
		<!-- MATRIC NUMBER Element -->
	    <div id="signin-placeholder">
		  <label for="matricNo">Matric Number</label>
		  <div class="input-container" id="input-container">
		    <input type="" id="matricNo" disabled placeholder="Student Matric No" value="<?php echo htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8'); ?>" />
			<i class="ph-bold ph-student"></i>
		  </div>
		  <span class="error" id="matricNoErr"></span><br />
		</div><br />
		
		<!-- Messages -->
		<div class="messages">
		  <div id="st-error"></div>
		  <div id="st-success"></div>
		</div><br /><br />
		
		<button type="button" id="registerBtn" class="">
		  <i class="ph-bold ph-fingerprint"></i> Register Passkey
		</button><br />
		
	  </div>
	</section>
	
	<script type="text/javascript">
	  // Student identity is rendered server-side from the session — never taken from user input.
	  const studentId = <?php echo json_encode($studentId); ?>;
	  const studentEmail = <?php echo json_encode($studentMail); ?>;
	  const studentDevice = <?php echo json_encode($studentDevice); ?>;
	  
	  console.log('Student Device ID:', studentDevice);
	  
	  const registerBtn = document.querySelector("#registerBtn");
	  const errorBox = document.querySelector("#st-error");
	  const successBox = document.querySelector("#st-success");
	  
	</script>
	<script src="/dist/js/register-passkey.js"></script>
  </body>
</html>