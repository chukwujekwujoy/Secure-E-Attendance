<?php

/* mail head */
require_once __DIR__ . '/../../config/mailhead.php';
/* Database file */
require_once __DIR__ . '/../../config/dbPlay.php';
/* tokens generator */
require_once __DIR__ . '/../../config/cdecs.php';
/* Dynamic domain script */
require_once __DIR__ . '/../../config/domain-host.php';


/* Input placeholders */
$uID = $urMail = $upiw = $ucpiw = $now = "";

/* Custom Input errors placeholders */
$urMail_err = $upiw_err = $upiw_err1 = $ucpiw_err = "";

/* Custom System errors placeholders */
$jowin_err = $jowin_erra = $jowin_errb = $jowin_errc = $jowin_errd = $jowin_erre = $jowin_errf = $jowin_errg = '';

/* JS Script manipulator */
$jsSnippet = $jsScript = "";

/* Image placeholders  */
$logo = "https://tellprotocol.com/FLogo.webp";
$logo1 = "https://tellprotocol.com/FLogo1.webp";

/* Domain placeholders  */
$hostUrl = getDomain();



if ($_SERVER["REQUEST_METHOD"] === "POST") {
	
	// User mail-input handler
	if(empty(trim($_POST['usrmail']))){
		$urMail_err = htmlspecialchars("Kindly input your email address!");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#sl-email").focus();
		document.querySelector("#usrmailErr").innerHTML = `$urMail_err`;</script>
		EOD;
	} elseif(filter_var(trim($_POST['usrmail']), FILTER_VALIDATE_EMAIL) === false){
		$urMail_err = htmlspecialchars("Invalid Email Address!");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#sl-email").focus();document.querySelector("#usrmailErr").innerHTML = `$urMail_err`;</script>
		EOD;
	} else{
		$sql = '
		SELECT 1
		FROM usersTemp
		WHERE userEmil = :maile
		
		UNION
		
		SELECT 1
		FROM lecturerDetails
		WHERE userEmil = :maile
		
		LIMIT 1		
		';

		if($stmt = $pdo->prepare($sql)){
			$param_maile = trim($_POST['usrmail']);
			$stmt->bindParam(":maile", $param_maile, PDO::PARAM_STR);
			if($stmt->execute()){
				
				if($stmt->fetch()){
					$urMail_err = "This Email Address has already been registered!";
					
					$jsSnippet = <<<EOD
					<script type="text/javascript">
					  document.querySelector("#sl-email").focus();
					</script>
					EOD;
				} else {
					$urMail = trim($_POST['usrmail']);
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
		<script type="text/javascript">document.querySelector("#sl-pwrd").focus();document.querySelector("#usrpinErr").innerHTML = `$upiw_err`;</script>
		EOD;
	} elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $_POST['pwrd'])){
		$upiw_err1 = htmlspecialchars("Password cannot be less than 8 characters and must contain at least: ") . '<br />' . htmlspecialchars("1 UPPERCASE letter") . '<br />' . htmlspecialchars("1 lowercase letter") . '<br />' . htmlspecialchars("1 digit") . '<br />' . htmlspecialchars("1 special character");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#pwrd").focus();document.querySelector("#st-error").innerHTML = `$upiw_err1`;</script>
		EOD;
	} else{
		$upiw = $_POST['pwrd'];
	}
	
	// User confirm-password-input handler
	if(empty($_POST['cpwrd'])){
		$ucpiw_err = htmlspecialchars("Kindly confirm password!");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#cpwrd").focus();document.querySelector("#cusrpinErr").innerHTML = `$ucpiw_err`;</script>
		EOD;
	} else{
		$ucpiw = $_POST['cpwrd'];
		if(empty($ucpiw_err) && ($ucpiw !== $upiw)){
			$ucpiw_err = htmlspecialchars("Password does not match! Kindly confirm your password");
			$jsSnippet = <<<EOD
			<script type="text/javascript">document.querySelector("#cpwrd").focus();</script>
			EOD;
		}
	}
	
	// Verification Code handler
	$uID = strtoupper(gencos());
	
	// Verification Token handler
	$tokes = generate_uuidv4();
	
	
	
	if(empty($urMail_err) && empty($jowin_err) && empty($upiw_err) && empty($upiw_err1) && empty($ucpiw_err) && !empty($uID) && !empty($tokes)){
		
		$sql = 'INSERT INTO usersTemp (userID, userEmil, usrPin, vericodes, eraValid) VALUES (:uID, :urMail, :upin, :vercode, :eraValid)';
		
		if($stmt = $pdo->prepare($sql)){
			$stmt->bindParam(":uID", $param_uID, PDO::PARAM_STR);
			$stmt->bindParam(":urMail", $param_urMail, PDO::PARAM_STR);
			$stmt->bindParam(":vercode", $param_vercode, PDO::PARAM_STR);
			$stmt->bindParam(":upin", $param_upin, PDO::PARAM_STR);
			$stmt->bindParam(":eraValid", $param_eraValid, PDO::PARAM_STR);
			
			$param_uID = $uID;
			$param_urMail = $urMail;
			$param_upin = password_hash($upiw, PASSWORD_BCRYPT);
			$param_vercode = $tokes;
			$param_eraValid = time();
			
			
			$verMail = $hostUrl . "/auth/confirm-email.php?user=" . $uID . "&nonce=" . $param_eraValid . "&usercode=" . $tokes . "&action=email-verification";
			
			if($stmt->execute()){
				
				// Mailing Agent
				$mail->setFrom("joelnonye@gmail.com", "FUTO E-ATTENDANCE PROJECT");
				$mail->addAddress($urMail);
				$mail->Subject = "Verify your Email";
				$mail->isHTML(true);
				$mail->Body = "
				<!DOCTYPE html>
				<html>
				<head>
				  <meta charset='UTF-8'>
				  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
				  
				  <style>
				    /* LIGHT MODE DEFAULT */
					body {
						background-color: #f4f4f7;
						color: #24292f;
					}
					
					/* DARK MODE SUPPORT */
					@media (prefers-color-scheme: dark) {
						body {
							background-color: #0d1117 !important;
							color: #e6edf3 !important;
						}
						.email-container {
							background-color: #161b22 !important;
						}
						.text {
							color: #e6edf3 !important;
						}
						.muted {
							color: #8b949e !important;
						}
						.button a {
							background-color: #238636 !important;
						}
					}
					
					/* MOBILE */
					@media only screen and (max-width: 600px) {
						.container {
							width: 100% !important;
						}
						.padding {
							padding: 20px !important;
						}
						h1 {
							font-size: 22px !important;
						}
						.button a {
							display: block !important;
							width: 95% !important;
						}
					}
					
				  </style>
				  
				</head>
				
				<body style='margin:0; padding:0; font-family: Arial, sans-serif;'>
				  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f7;'>
				    <tr>
					  <td align='center'>
					    
						<!-- CONTAINER -->
						<table class='container email-container' width='600' cellpadding='0' cellspacing='0'
						style='max-width:600px; width:100%; background:#ffffff; border-radius:8px; overflow:hidden;'>
						
						  <!-- HEADER -->
						  <tr>
						    <td align='center' style='padding:25px;'>
							  <picture>
							    <!-- Dark mode version (shown when student prefers dark) -->
								<source srcset='$logo' media='(prefers-color-scheme: dark)'>
								
								<!-- Light mode version (shown when student prefers light) -->
								<img src='$logo1' alt='School Logo'>
								
								
								
								
							  </picture>
							</td>
						  </tr>
						  
						  
						  <tr>
						    <td align='center' style='padding:25px;'>
							  <h1 class='text' style='margin:0; color:#24292f;'>FUTO E-ATTENDANCE</h1>
							</td>
						  </tr>
						  
						  <!-- CONTENT -->
						  <tr>
						    <td class='padding text' style='padding:30px; color:#24292f; font-size:15px; line-height:1.6;'>
							  
							  <p>Dear Lecturer,</p>
							  
							  <p>Kindly click on the link below to verify your email address:</p>
							  
							  <!-- BUTTON -->
							  <table class='button' width='100%' cellpadding='0' cellspacing='0' style='margin:25px 0;'>
							    <tr>
								  <td align='center'>
								    <a href='$verMail' style='background:#2ea44f; color:#ffffff; text-decoration:none; padding:14px 24px; border-radius:6px; font-weight:bold; display:inline-block;'>Verify your Email</a>
								  </td>
								</tr>
							  </table>
							  
							  <p>OR copy the link below and paste into your browser's address bar</p>
							  <p>
							    <a href='$verMail'
style='color:#2ea44f; text-decoration:none; font-weight:bold;'>$verMail</a>
							  </p>
							  
							  <p style='font-weight:bold;'>
							    The link is valid only for 15minutes.
							  </p>
							  
							</td>
						  </tr>
						  
						  <!-- FOOTER -->
						  <tr>
						    <td class='muted' style='padding:20px; text-align:center; font-size:12px; color:#6a737d;'>
							  <p>
							    <a href='x.com/@ChidinmaCJ' style='color:inherit; text-decoration:none;'>X (Formerly Twitter)</a>
							    <a href='feedback@tellprotocol.com' style='color:inherit; text-decoration:none;'>Feedback</a>	
							  </p>
							  
							</td>
						  </tr>
						  
						</table>
						
					  </td>
					</tr>
				  </table>
				  
				</body>
				</html>
				";
				
				$mail->AltBody = "
				E-ATTENDANCE
				
				Email Verification
				
				
				Dear Lecturer,
				
				Kindly click on the link below to verify your email address:
				$verMail
				
				OR copy the link and paste into your browser's address bar
				
				This link is valid only for 15minutes.
				
				Best wishes,
				The E-ATTENDANCE Team
				
				
				Kindly disregard this message if you do not recognize this action!
				";
				if ($mail->send()) {
					$jsSnippet = <<<EOD
					<script type="text/javascript">alert("A verification link has been sent to your provied email address. Kindly check your email and follow the instructions to verify your email.")</script>
					EOD;
				} else{
					echo $mail->ErrorInfo;
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
	<title>Lecturers SignUp Page | FUTO</title>
	
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
	
	<section class="root" id="root" title="SignUp Form">
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
		<form id="signup" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
		  
		  <!-- LECTURER EMAIL ADDRESS Element -->
		  <div class="" title="Input Your Email Address">
		    <label for="usrmail">Email Address</label>
			<div class="input-container" id="input-container">
			  <input type="email" id="usrmail" autofocus name="usrmail" placeholder="Email Address" required value="<?php echo htmlspecialchars($urMail);?>" />
			  <i class="ph-bold ph-at"></i>
			</div>
		    <span class="error" id="usrmailErr"><?php echo htmlspecialchars($urMail_err);?></span><br />
		  </div>
		  
		  <!-- PASSWORD Element -->
		  <div class="" title="Input Your Password">
		    <label for="usrpin">Password</label>
			<div class="input-container" id="input-container">
			  <input type="password" id="usrpin" name="pwrd" placeholder="Password" required value="<?php echo htmlspecialchars($upiw);?>" />
			  <i class="ph-bold ph-eye" id="vi1" style="cursor: pointer;"></i>
			</div>
		    <span class="error" id="usrpinErr"><?php echo htmlspecialchars($upiw_err);?></span><br />
		  </div>
		  
		  <!-- CONFIRM PASSWORD Element -->
		  <div class="" title="Confirm your Password">
		    <label for="cusrpin">Confirm Password</label>
			<div class="input-container" id="input-container">
			  <input type="password" id="cusrpin" name="cpwrd" placeholder="Confirm Password" required value="<?php echo htmlspecialchars($ucpiw);?>" />
			  <i class="ph-bold ph-eye" id="vi2" style="cursor: pointer;"></i>
			</div>
			<span class="error" id="cusrpinErr"><?php echo htmlspecialchars($ucpiw_err);?></span><br />
		  </div>
		  
		  
		  <!-- Messages -->
		  <div class="messages" id="">
		    <div class="" id="st-error"></div>
		  </div><br />
		  
		  <!-- Submit Button Element -->
		  <button type="submit" id="submitForm" class="" title="Sign Up">SIGN UP</button><br />
		  
		</form>
	  
	  <div class="">
	    <div class="">
		  <span style="">Already have an account?  <a href="./signin.php">SignIn Here</a></span>
		</div>
	  </div>
	  
	  </div>
	</section>
	
	<script type="module">
	  import { FormValidator } from '../dist/js/formValidator.js';
	  import { setupPasswordToggle } from '../dist/js/formValidator.js';
	  
	  const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
	  
	  
	  new FormValidator({
		  form: document.querySelector('#signup'),
		  email: document.querySelector('#usrmail'),
		  password: document.querySelector('#usrpin'),
		  confirmPassword: document.querySelector('#cusrpin'),
		  submitBtn: document.querySelector('#submitForm'),
		  emailError: document.querySelector('#usrmailErr'),
		  passwordError: document.querySelector('#usrpinErr'),
		  confirmPasswordError: document.querySelector('#cusrpinErr'),
		  inputDiv: document.querySelector('#tellInputDiv'),
		  
		  passwordRegex: strongPasswordRegex
	  });
	  
	  setupPasswordToggle(document.querySelector('#cusrpin'), document.querySelector('#vi2'));
	  setupPasswordToggle(document.querySelector('#usrpin'), document.querySelector('#vi1'));
	</script>
	<?php echo $jsScript; echo $jsSnippet;?>
  </body>
</html>
