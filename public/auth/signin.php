<?php

/* Database file */
require_once __DIR__ . '../../config/dbPlay.php';
/* Session Manager file */
require_once __DIR__ . '../../config/sessionManager.php';



/* Initialize session manager */
use SessionManager\SessionManager;

/* 30 minutes lifetime */
$ses = new SessionManager(3600);


/* Input placeholders */
$urMail = $upiw = $usrnamee = $now = "";

/* Input errors placeholders */
$urMail_err = $upiw_err = $upiw_err1 = "";

/* Other messages placeholders  */
$msg = $verily = $tokes = "";

/* System errors placeholders */
$jowin_err = $jowin_erra = $jowin_errb = $jowin_errc = $jowin_errd = $jowin_erre = $jowin_errf = $jowin_errg = '';

/* JS Script manipulator */
$jsSnippet = $jsScript = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	
	/* Lecturers email input validation */
	if(empty(trim($_POST['usrmail']))){
		$urMail_err = htmlspecialchars("Kindly input your email address!");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#sl-usrmail").focus();</script>
		EOD;
	} elseif(filter_var(trim($_POST['usrmail']), FILTER_VALIDATE_EMAIL) === false){
		$urMail_err = htmlspecialchars("Invalid Email Address!");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#sl-usrmail").focus();</script>
		EOD;
	} else{
		$sql = 'SELECT userEmil FROM lecturerDetails WHERE userEmil = :maile';

		if($stmt = $pdo->prepare($sql)){
			$stmt->bindParam(":maile", $param_maile, PDO::PARAM_STR);
			
			$param_maile = trim($_POST['usrmail']);
			if($stmt->execute()){
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				if($result){
					$urMail = trim($_POST['usrmail']);
				} else{
					$urMail_err = htmlspecialchars("Invalid Account!");
					$jsSnippet = <<<EOD
					<script type="text/javascript">
					  document.querySelector("#sl-usrmail").focus();
					</script>
					EOD;
				}
				
			} else{
				$jowin_err = htmlspecialchars("Oops! Something went wrong.") . '<br />' . htmlspecialchars("Please try again later!");
				$jsSnippet = <<<EOD
				<script type="text/javascript">
				  document.querySelector("#sl-message-display").innerHTML = "<i class=''>" + "{$jowin_err}" + "</i>";
				  document.querySelector("#sl-message").showModal();
				  setTimeout(()=>{
					  document.querySelector("#sl-message").close();
				  }, 60000);
				</script>
				EOD;
		
			}
		}
		unset($stmt);
	}
	
	// User password-input handler
	if(empty($_POST['pwrd'])){
		$upiw_err = htmlspecialchars("Please input your Password!");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#sl-usrpin").focus();</script>
		EOD;
	} elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $_POST['pwrd'])){
		$upiw_err1 = '<i>' . htmlspecialchars("Password cannot be less than 8 characters and must contain at least: ") . '<br />' . htmlspecialchars("1 UPPERCASE letter") . '<br />' . htmlspecialchars("1 lowercase letter") . '<br />' . htmlspecialchars("1 digit") . '<br />' . htmlspecialchars("1 special character") . '</i>';
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#usrpin").focus();document.querySelector("#st-error").innerHTML = `$upiw_err1`;</script>
		EOD;
	} else{
		$upiw = $_POST['pwrd'];
	}
		
	
	if(empty($urMail_err) && empty($jowin_err) && empty($upiw_err) && empty($upiw_err1)){
		
		$sql = 'SELECT * FROM lecturerDetails WHERE userEmil = :maile';
		
		if($stmt = $pdo->prepare($sql)){
			$stmt->bindParam(":maile", $param_maile, PDO::PARAM_STR);
			
			if($stmt->execute()){
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				if($result){
					$usrPassKey = $result["usrPin"];
					$usrID = $result["userID"];
					
					if (password_verify($upiw, $usrPassKey)) {
						/* Start Session */
						$ses->Start();
						/* Bind To Client */
						$ses->bindToClient();
						/* Set session authentication = true */
						$ses->set('authenticated', true);
						/* Set session variables */
						$ses->Set("usrEmaile", $urMail);
						$ses->Set("usrID", $usrID);
						
						$jsScript = <<<EOD
						<script type="text/javascript">
						  setTimeout(()=>{window.location.href = "/tutors/dashboard.php";}, 100);
						</script>
						EOD;
					} else {
						$upiw_err = 'Invalid Account!';
						$jsSnippet = <<<EOD
						<script type="text/javascript">document.querySelector("#sl-usrpin").focus();</script>
						EOD;
					}
				} else{
					$usrMaile_err = 'Invalid Login Details!';
					$jsSnippet = <<<EOD
					<script type="text/javascript">document.querySelector("#sl-usrmail").focus();</script>
					EOD;
				}
				
			}
			
		}
		unset($stmt);
		
	}
	
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
	<title>Lecturers SignIn Page | FUTO</title>
	
	<!-- Phosphor Icons CSS File -->
	<link href="/dist/phosphor-icons/duotone/style.css" rel="stylesheet">
	<link href="/dist/phosphor-icons/bold/style.css" rel="stylesheet">
	
	<!-- Peload Fonts File -->
	<link rel="preload" href="/dist/css/fonts/trebuc.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="/dist/css/fonts/trebuc.woff" as="font" type="font/woff" crossorigin>
	<link rel="preload" href="/dist/css/fonts/Alef-Regular.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="/dist/css/fonts/Alef-Regular.woff" as="font" type="font/woff" crossorigin>
	
	<!-- External Font CSS  -->
	<link href="/dist/css/fonts.css" rel="stylesheet">
	
	<!-- External Stylesheet File -->
	<link href="/dist/css/students.css" rel="stylesheet">
	<link href="/dist/css/preloader.css" rel="stylesheet">
	
	<link href="" rel="stylesheet">
	<link href="" rel="stylesheet">
	
	<!-- Site favicon Tag -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico" />
	
	<!-- My Manifest Tag (JSON format) -->
	<link rel="manifest" href="./manifest.json" />
  </head>
  <body>
	
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
		<form id="signin" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
		  
		  <!-- LECTURER EMAIL ADDRESS Element -->
		  <div class="" title="Input Your School Matric No">
		    <label for="usrmail">Email Address</label>
			<div class="input-container" id="input-container">
			  <input type="email" id="usrmail" autofocus name="usrmail" placeholder="Email Address" required value="<?php echo htmlspecialchars($urMail);?>" />
			  <i class="ph-bold ph-at"></i>
			</div>
		    <span class="error" id="usrmailErr"><?php echo htmlspecialchars($urMail_err);?></span><br /><br />
		  </div>
		  
		  <!-- PASSWORD Element -->
		  <div class="" title="Input Your School Matric No">
		    <label for="usrpin">Password</label>
			<div class="input-container" id="input-container">
			  <input type="password" id="usrpin" name="pwrd" placeholder="Password" required value="<?php echo htmlspecialchars($upiw);?>" />
			  <i class="ph-bold ph-eye" id="vi1" style="cursor: pointer;"></i>
			</div>
		    <span class="error" id="usrpinErr"><?php echo htmlspecialchars($upiw_err);?></span><br />
		  </div>
		  
		  
		  <!-- Messages -->
		  <div class="messages" id="">
		    <div class="" id="st-error"></div>
		  </div><br />
		  
		  <!-- Submit Button Element -->
		  <button type="submit" id="submitForm" class="" title="Sign In">SIGN IN</button><br />
		  
		</form>
		
		<div class="">
		  <div class="">
		    <span style="">Create a new account?  <a href="./signup.php">SignUp Here</a></span>
		  </div>
		  <div class="">
		    <span style="">Forgotten your Password?  <a href="./forgotten-password.php">Reset it Here</a></span>
		  </div>
		</div>
	  
	  </div>
	</section>
	
	<script type="module">
	  import { FormValidator } from '../dist/js/formValidator.js';
	  import { setupPasswordToggle } from '../dist/js/formValidator.js';
	  
	  const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
	  
	  new FormValidator({
		  form: document.querySelector('#signin'),
		  email: document.querySelector('#usrmail'),
		  password: document.querySelector('#usrpin'),
		  submitBtn: document.querySelector('#submitForm'),
		  emailError: document.querySelector('#usrmailErr'),
		  passwordError: document.querySelector('#usrpinErr'),
		  inputDiv: document.querySelector('#tellInputDiv'),
		  
		  passwordRegex: strongPasswordRegex
	  });
	  
	  setupPasswordToggle(document.querySelector('#usrpin'), document.querySelector('#vi1'));
	</script>
	<?php echo $jsScript;?><?php echo $jsSnippet;?>
  </body>
</html>