<?php

/* Database file */
require_once __DIR__ . '../../config/dbPlay.php';
/* Session Manager file */
require_once __DIR__ . '../../config/sessionManager.php';
require_once __DIR__ . '../../config/domain-host.php';

/* Initialize session manager */
use SessionManager\SessionManager;

$ses = new SessionManager(1800);

/* Start Session */
$ses->Start();
/* Domain placeholders  */
$hostUrl = getDomain();


/**
 * Lecturer auth
 * ─────────────
 * This page expects a lecturer to already be signed in (via the login flow) with the tutor's identity stored in $ses.
 */

/* Check if session has expired */
if ($ses->isExpired()) {
	/* Session expired, user needs to login again */
    header('Location: /auth/signin.php');
    exit;
}

/* Check if session is authenticated */
if ($ses->get('authenticated') !== true) {
	/* Session authentication check failed, user needs to login again */
    header('Location: /auth/signin.php');
    exit;
}

/* Verify the client fingerprint */
$ses->bindToClient();


/*
 * Retrieve authenticated user information
 * from the session.
 */
 
/* Get lecturer ID from authenticated session */
$tutorId = $ses->get('usrID');
/* Get Lecturer email address from authenticated session */
$tutorMail = $ses->get('usrEmaile');

if (!$tutorId || !$tutorMail) {
	/* User information is empty. Destroy session and user must signin again */
	$ses->destroy();
	header('Location: /auth/signin.php');
    exit;
}




/*  */
/*  */

$initials = 'L';
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
	<title>Lecturer Portal | FUTO</title>
	
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
	<link rel="stylesheet" href="/dist/css/fonts.css">
	
	<!-- External Stylesheet File -->
	<link href="/dist/css/main.css" rel="stylesheet">
	<link href="" rel="stylesheet">
	
	<!-- QRCode Javascript File -->
	<script src="/dist/js/qrcode@1_5_1/qrcode.min.js"></script>
	
	<!-- ZXing Javascript File -->
	<script src="/dist/js/zxing@0_19_1/index.min.js"></script>
	
	<!-- Site favicon Tag -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico" />
	
	<!-- My Manifest Tag (JSON format) -->
	<link rel="manifest" href="./manifest.json" />
	
  </head>
  <body>
    
	<!-- ════════════ HEADER ════════════ -->
	<header>
	  <div class="header-inner">
	    <div class="logo-panel">
		  <div class="logo-panel-heading" id="" title="School Logo">
		    <a class="logo" href="#">
			  <picture>
			    <!-- Dark mode version (shown when student prefers dark) -->
				<source srcset="/dist/images/FLogo.png" media="(prefers-color-scheme: dark)">
				
				<!-- Light mode version (shown when student prefers light) -->
				<img src="/dist/images/FLogo1.png" alt="School Logo">
			  </picture>
			</a>
		  </div>
		</div>
		
		<div class="header-right">
		  <div class="tutor-pill">
		    <div class="avatar"><?php echo htmlspecialchars($initials);?></div>
			<div>
			  <div class="tutor-name"><?php echo htmlspecialchars($tutorMail); ?></div>
			  <div class="tutor-id">ID: <?php echo htmlspecialchars($tutorId);?></div>
			</div>
		  </div>
		</div>
	  </div>
	</header>
	
	<!-- ════════════ BODY ════════════ -->
	<div class="app-body">
	  
	  <!-- ── Sidebar ── -->
	  <aside class="sidebar">
	    <div class="sidebar-group">
		  <div class="sidebar-label">Overview</div>
		  <a class="nav-item active" data-page="dashboard"><i class="fas fa-gauge-high"></i> Dashboard</a>
		</div>
		
		<hr class="sidebar-divider" />
		
		<div class="sidebar-group">
		  <div class="sidebar-label">Sessions</div>
		  <a class="nav-item" data-page="create-session"><i class="ph-bold ph-qr-code"></i> Create Session</a>
		</div>
		
		<hr class="sidebar-divider" />
		
		<div class="sidebar-group">
		  <div class="sidebar-label">Attendance</div>
		  <a class="nav-item" data-page="attendance"><i class="fas fa-list-check"></i> Attendance Viewer</a>
		</div>
	  </aside>
	  
	  <!-- ── Main ── -->
	  <main>
	    
		<!-- ══════════════════════════════
		     PAGE: DASHBOARD
		══════════════════════════════ -->
		<div class="page active" id="page-dashboard">
		  <div class="page-header">
		    <div>
			  <div class="page-title">Lecturer <span>Dashboard</span></div>
			  <div class="page-sub">Welcome back, <strong><?php echo htmlspecialchars($tutorMail); ?></strong> · Tutor ID <span style="font-family:var(--mono)"><?php echo htmlspecialchars($tutorId); ?></span></div>
			</div>
			<button class="btn btn-primary" onclick="navigate('create-session')">
			  <i class="fas fa-qrcode"></i> New Session
			</button>
		  </div>
		  
		  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
		    <div class="card">
			  <div class="card-title">Create a session</div>
			  <div class="card-sub">Generate a QR code students scan to sign attendance for any course.</div>
			  <button class="btn btn-primary" style="width:100%" onclick="navigate('create-session')">
			    <i class="fas fa-qrcode"></i> Create Session
			  </button>
			</div>
			
			<div class="card">
			  <div class="card-title">Jump to a course</div>
			  <div class="card-sub">Open the attendance viewer straight to a course's sessions and records.</div>
			  <div class="form-group" style="margin-bottom:12px">
			    <label>Course code</label>
				<input type="text" id="dash-course-jump" placeholder="e.g. CSC301" />
			  </div>
			  <button class="btn btn-outline" style="width:100%" onclick="jumpToCourse()">
			    <i class="fas fa-arrow-right"></i> View Attendance
			  </button>
			</div>
		  </div>
		  
		  <div class="table-wrap" style="margin-top:24px">
		    <div class="table-head">
			  <h3>Sessions created this visit</h3>
			</div>
			<table>
			  <thead>
			    <tr>
				  <th>Course</th>
				  <th>Session ID</th>
				  <th>Created At</th>
				  <th></th>
				</tr>
			  </thead>
			  <tbody id="dash-recent-tbody">
			    <td colspan="4">
				  <div class="empty-state" style="padding:20px"><i class="fas fa-clock-rotate-left"></i><p>Sessions you create will show up here for this visit.</p></div>
				</td>
			  </tbody>
			</table>
		  </div>
		</div>
		
		<!-- ══════════════════════════════
		     PAGE: CREATE SESSION + QR
		═══════════════════════════════ -->
		<div class="page" id="page-create-session">
		  <div class="page-header">
		    <div>
			  <div class="page-title">Create <span>Session</span></div>
			  <div class="page-sub">Generate a QR link students scan to sign attendance</div>
			</div>
		  </div>
		  
		  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
		    
			<!-- Form -->
			<div>
			  <div class="card" style="margin-bottom:18px">
			    <div class="card-title">Session details</div>
				<div class="card-sub">Choose a course and set a unique session ID · creating as <strong><?php echo htmlspecialchars($tutorMail);?></strong></div>
				
				<div class="form-group">
				  <label>Year &amp; Semester</label>
				  <select id="cs-year-semester">
				    <option>— Select year &amp; semester —</option>
					<option value="year_one:harmattan_semester">Year 1 — Harmattan Semester</option>
					<option value="year_one:rain_semester">Year 1 — Rain Semester</option>
					<option value="year_two:harmattan_semester">Year 2 — Harmattan Semester</option>
					<option value="year_two:rain_semester">Year 2 — Rain Semester</option>
					<option value="year_three:harmattan_semester">Year 3 — Harmattan Semester</option>
					<option value="year_three:rain_semester">Year 3 — Rain Semester</option>
					<option value="year_four:harmattan_semester">Year 4 — Harmattan Semester</option>
					<option value="year_four:rain_semester">Year 4 — Rain Semester</option>
					<option value="year_five:harmattan_semester">Year 5 — Harmattan Semester</option>
					<option value="year_five:rain_semester">Year 5 — Rain Semester</option>
				  </select>
				</div>
				<div class="form-group">
				  <label>Course</label>
				  <select id="cs-course-select" disabled>
				    <option>— Select year &amp; semester first —</option>
				  </select>
				</div>
				<div class="form-group">
				  <label>Session ID</label>
				  <input type="text" id="session-id-input" placeholder="e.g. 4A4A23Z" maxlength="30" />
				</div>
				
				<div style="display:flex;gap:10px">
				  <button class="btn btn-amber" style="flex:1" onclick="autoSessionId()">
				    <i class="fas fa-dice"></i> Auto-generate ID
				  </button>
				  <button class="btn btn-primary" style="flex:1" onclick="createSession()">
				    <i class="fas fa-calendar-plus"></i> Create Session
				  </button>
				</div>
			  </div>
			  
			  <!-- Existing sessions for chosen course -->
			  <div class="table-wrap" style="margin-bottom:0">
			    <div class="table-head">
				  <h3 id="sessions-list-title">Sessions for this course</h3>
				</div>
				<table>
				  <thead>
				    <tr>
					  <th>Session ID</th>
					  <th>Created At</th>
					  <th>Sign-ins</th>
					  <th>Status</th>
					</tr>
				  </thead>
				  <tbody id="sessions-tbody">
				    <tr><td colspan="4">
					  <div class="empty-state" style="padding:20px">
					    <i class="fas fa-calendar-xmark"></i><p>Select a course to see its sessions.</p>
					  </div>
					</td></tr>
				  </tbody>
				</table>
			  </div>
			</div>
			
			<!-- QR Panel -->
			<div class="qr-panel">
			  <div class="card-title">QR Code</div>
			  <div class="card-sub" style="margin-bottom:0">Students scan this to sign attendance</div>
			  
			  <div class="qr-placeholder-box" id="qr-placeholder">
			    <i class="ph-bold ph-qr-code"></i>
				<span>QR appears after creating a session</span>
			  </div>
			  <div id="qr-canvas"></div>
			  
			  <!-- Countdown -->
			  <div id="countdown-wrap" style="display:none;margin-top:8px">
			    <div class="countdown-ring">
				  <svg viewBox="0 0 54 54" width="54" height="54">
				    <circle class="track" cx="27" cy="27" r="24"/>
					<circle class="fill"  cx="27" cy="27" r="24" id="cring"/>
				  </svg>
				  <div class="label" id="clabel">10:00</div>
				</div>
				<div class="qr-expiry active" id="expiry-text">Valid for a while</div>
			  </div>
			  <div id="expired-notice" style="display:none;margin-top:8px;font-size:13px;color:var(--danger);font-weight:600">
			    <i class="fas fa-clock"></i> Session expired — create a new one
			  </div>
			  
			  <div class="qr-url-box" id="qr-url-box"></div>
			  
			  <div style="display:flex;gap:10px;margin-top:16px;width:100%">
			    <button class="btn btn-outline" style="flex:1" id="copy-url-btn" onclick="copyURL()" disabled>
				  <i class="fas fa-copy"></i> Copy URL
				</button>
				<button class="btn btn-outline" style="flex:1" id="print-qr-btn" onclick="printQR()" disabled>
				  <i class="fas fa-print"></i> Print QR
				</button>
			  </div>
			</div>
		  </div>
		</div>
		
		<!-- ══════════════════════════════
		     PAGE: ATTENDANCE VIEWER
		══════════════════════════════ -->
		<div class="page" id="page-attendance">
		  <div class="page-header">
		    <div>
			  <div class="page-title">Attendance <span>Viewer</span></div>
			  <div class="page-sub">Past and present attendance, by session, by course, or both</div>
			</div>
			<div style="display:flex;gap:8px" id="av-view-toggle">
			  <button class="btn btn-outline btn-sm is-active" data-view="both" onclick="setAttendanceView('both')">Combined</button>
			  <button class="btn btn-outline btn-sm" data-view="course" onclick="setAttendanceView('course')">By Course</button>
			  <button class="btn btn-outline btn-sm" data-view="session" onclick="setAttendanceView('session')">By Session</button>
			</div>
		  </div>
		  
		  <div class="card" style="margin-bottom:20px">
		    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
			  <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px">
			    <label>Course code</label>
				<input type="text" id="av-course-input" placeholder="e.g. CSC301" />
			  </div>
			  <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px">
			    <label>Session</label>
				<select id="av-session-select">
				  <option>— Load a course first —</option>
				</select>
			  </div>
			  <button class="btn btn-primary" onclick="loadAttendance()">
			    <i class="fas fa-rotate"></i> Load
			  </button>
			</div>
		  </div>
		  
		  <!-- Course-wide block -->
		  <div id="av-course-block" style="display:none">
		    <div class="stats-row" id="av-course-stats"></div>
			<div class="table-wrap">
			  <div class="table-head">
			    <h3>Per-Student Attendance</h3>
				<span id="av-course-label" style="font-size:12px;color:var(--muted)"></span>
			  </div>
			  <table>
			    <thead>
				  <tr>
				    <th>Reg. No.</th>
					<th>Sessions Attended</th>
					<th>Out of</th>
					<th>Rate</th>
					<th>Status</th>
				  </tr>
				</thead>
				<tbody id="av-course-tbody">
				  <tr><td colspan="5">
				    <div class="empty-state">
					  <i class="fas fa-chart-bar"></i><p>Load a course to see per-student attendance.</p>
					</div>
				  </td></tr>
				</tbody>
			  </table>
			</div>
			<div class="table-wrap">
			  <div class="table-head">
			    <h3>Sessions</h3>
			  </div>
			  <table>
			    <thead><tr>
				  <th>Session ID</th>
				  <th>Created At</th>
				  <th>Sign-ins</th>
				  <th></th>
				</tr></thead>
				<tbody id="av-sessions-tbody"></tbody>
			  </table>
			</div>
		  </div>
		  
		  <!-- Session-specific block -->
		  <div id="av-session-block" style="display:none">
		    <div class="table-wrap">
			  <div class="table-head">
			    <h3>Attendees</h3>
				<span id="av-session-count" style="font-size:12px;color:var(--muted)"></span>
			  </div>
			  <table>
			    <thead><tr>
				  <th>#</th>
				  <th>Reg. No.</th>
				  <th>Signed At</th>
				</tr></thead>
				<tbody id="av-session-tbody">
				  <tr><td colspan="3">
				    <div class="empty-state">
					  <i class="fas fa-users"></i><p>Select a session to load its roster.</p>
					</div>
				  </td></tr>
				</tbody>
			  </table>
			</div>
		  </div>
		  
		  <div class="empty-state" id="av-empty">
		    <i class="fas fa-list-check"></i><p>Enter a course code above and click Load.</p>
		  </div>
		</div>
		
	  </main>
	  
	</div>
	
	<!-- ═══════ DIALOG: SESSION DETAILS ═══════ -->
	<dialog class="modal" id="session-modal">
	  <!-- Dialog Header -->
	  <div class="modal-header">
	    <div>
		  <h2 class="modal-title" id="modal-title">ATTENDANCE DETAILS</h2>
		</div>
		
	  </div>
	  
	  <!-- Dialog Content -->
	  <div class="modal-body" id="modal-body">
	    
	  </div>
	</dialog>
	
	<!-- ═══════ TOAST ═══════ -->
	<div id="toast">
	  <i class="fas fa-circle-check ti"></i>
	  <div class="tm" id="toast-msg"></div>
	</div>
	
	
	<script>
	  // Setting PHP endpoints.
	  const API_BASE = '/api';
	  const BaseHost = '<?php echo htmlspecialchars($hostUrl);?>';
	  console.log(BaseHost);
	  // This is pointing to our student-facing sign-in / attendance page.
	  const QR_BASE_URL = BaseHost +'/attend.php';
	  console.log(QR_BASE_URL);
	  
	  const QR_VALIDITY_WINDOW = 10 * 60;   // seconds — how long a QR stays valid
	  
	  // Lecturer identity comes from the PHP session (see top of this file), not from the browser.
	  const LECTURER = {
		  name: <?php echo json_encode($tutorMail);?>,
		  tutor_id: <?php echo json_encode($tutorId);?>,
		  avatar: <?php echo json_encode($initials);?>
	  };
	  
	  let activeQR = null;
	  let countdownTimer = null;
	  let attendanceView = 'both';
	  let avSessions = [];   // normalized sessions for the currently loaded course
	  let recentSessions = [];  // sessions created during this visit — not persisted
	  
	  
	  
	  
	  
	</script>
	<script>
	  /**
	   * Lecturer flow: capture location, then resolve with geofence session data.
	   * Call this from another script to get { lat, lng, accuracy_m, radius_m },
	   * then submit it however that script needs to.
	   * 
	   * 
	   * 
	   * 
	   * 
	   */
	   
	   async function getGeofencedSessionData(radiusM = 300) {
		   const statusEl = document.querySelector('#session-status');
		   
		   if (!('geolocation' in navigator)) {
			   if (statusEl) statusEl.textContent = 'Your browser does not support geolocation. Cannot start session.';
			   return null;
		   }
		   
		   if (statusEl) statusEl.textContent = 'Getting your location…';
		   
		   return new Promise((resolve) => {
			   navigator.geolocation.getCurrentPosition(
				   (position) => {
					   const {
						   latitude,
						   longitude,
						   accuracy
					   } = position.coords;
					   
					   if (accuracy > 25) {
						   const proceed = confirm(
							   `Your location accuracy is low (±${Math.round(accuracy)}m). ` +
							   `Some valid students near the edge of the ${radiusM}m radius may get rejected. Continue anyway?`
							   
						   );
						   if (!proceed) {
							   if (statusEl) statusEl.textContent = 'Session start cancelled.';
							   resolve(null);
							   return;
						   }
					   }
					   
					   if (statusEl) statusEl.textContent = 'Location captured.';
					   
					   resolve({
						   lat: latitude,
						   lng: longitude,
						   accuracy_m: accuracy,
						   radius_m: radiusM,
					   });
				   },
				   () => {
					   if (statusEl) statusEl.textContent = 'Location permission denied. A session cannot start without location.';
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
	<script src="/dist/js/courses.js"></script>
	<script src="/dist/js/helpers.js"></script>
	<script src="/dist/js/qrcode.js"></script>
  </body>
</html>
