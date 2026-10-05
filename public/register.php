<?php

/* mail head */
require_once __DIR__ . '/config/mailhead.php';
/* Database file */
require_once __DIR__ . '/config/dbPlay.php';
/* tokens generator */
require_once __DIR__ . '/config/cdecs.php';
/* Dynamic domain script */
require_once __DIR__ . '/config/domain-host.php';


/* Input placeholders */
$uRegNo = $iniReg = $uID = $urMail = "";

/* Custom Input errors placeholders */
$urMail_err = $uRegNo_err = "";

/* Other messages placeholders  */
$msg = $verily = $tokes = "";

/* Custom System errors placeholders */
$jowin_err = $jowin_erra = $jowin_errb = $jowin_errc = $jowin_errd = '';

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
		<script type="text/javascript">document.querySelector("#usrmail").focus();
		document.querySelector("#usrmailErr").innerHTML = `$urMail_err`;</script>
		EOD;
	} elseif(filter_var(trim($_POST['usrmail']), FILTER_VALIDATE_EMAIL) === false){
		$urMail_err = htmlspecialchars("Invalid Email Address!");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#usrmail").focus();document.querySelector("#usrmailErr").innerHTML = `$urMail_err`;</script>
		EOD;
	} else{
		$sql = '
		SELECT 1
		FROM studentsTemp
		WHERE userEmil = :maile
		
		UNION
		
		SELECT 1
		FROM studentsDetails
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
					<script type='text/javascript'>
					  document.querySelector('#usrmail').value = "{$_POST['usrmail']}";
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
	
	// User Matric No input handler
	if(empty(trim($_POST['matricNo']))){
		$uRegNo_err = htmlspecialchars("Please input your Matric Number!");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#matricNo").focus();document.querySelector("#uRegNoErr").innerHTML = `$uRegNo_err`;</script>
		EOD;
	} elseif(!preg_match('/^(202[0-6])\d{7}$/', trim($_POST['matricNo']))){
		$uRegNo_err = htmlspecialchars("Matric Number must be exactly 11 digits and begin with 2020–2026!");
		$jsSnippet = <<<EOD
		<script type="text/javascript">document.querySelector("#matricNo").focus();document.querySelector("#uRegNoErr").innerHTML = `$uRegNo_err`;</script>
		EOD;
	} else{
		$sql = '
		SELECT 1
		FROM studentsTemp
		WHERE userID = :regisNo
		
		UNION
		
		SELECT 1
		FROM studentsDetails
		WHERE userID = :regisNo
		
		LIMIT 1		
		';
		
		if($stmt = $pdo->prepare($sql)){
			$regisNo = strtoupper(dechex(trim($_POST['matricNo'])));
			$param_regisNo = $regisNo;
			$stmt->bindParam(":regisNo", $param_regisNo, PDO::PARAM_STR);
			
			if($stmt->execute()){
				
				if($stmt->fetch()){
					$uRegNo_err = htmlspecialchars("This Matric Number has already been registered!");
					
					$jsSnippet = <<<EOD
					<script type='text/javascript'>
					  document.querySelector('#matricNo').value = "{$_POST['matricNo']}";
					</script>
					EOD;
				} else {
					$uRegNo = $regisNo;
				}
			} else{
				$jowin_err1 = htmlspecialchars("Oops! Something went wrong.") . '<br />' . htmlspecialchars("Please try again later!");
				$jsSnippet = <<<EOD
				<script type="text/javascript">
				  document.querySelector("#sl-message-display").innerHTML = "<i class=''>" + "{$jowin_err1}" + "</i>";
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
	
	// Verification Code handler
	$uID = $uRegNo;
	
	// Verification Token handler
	$tokes = generate_uuidv4();
	
	
	
	if(empty($urMail_err) && empty($jowin_err) && empty($jowin_err1) && empty($uRegNo_err) && !empty($uID) && !empty($tokes)){
		
		$sql = 'INSERT INTO studentsTemp (userID, userEmil, vericodes, eraValid) VALUES (:uID, :urMail, :vercode, :eraValid)';
		
		if($stmt = $pdo->prepare($sql)){
			$stmt->bindParam(":uID", $param_uID, PDO::PARAM_STR);
			$stmt->bindParam(":urMail", $param_urMail, PDO::PARAM_STR);
			$stmt->bindParam(":vercode", $param_vercode, PDO::PARAM_STR);
			$stmt->bindParam(":eraValid", $param_eraValid, PDO::PARAM_STR);
			
			$param_uID = $uID;
			$param_urMail = $urMail;
			$param_vercode = $tokes;
			$param_eraValid = time();
			
			
			$verMail = $hostUrl . "/confirm-email.php?user=" . $uID . "&nonce=" . $param_eraValid . "&usercode=" . $tokes . "&action=email-verification";
			
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
							  
							  <p>Dear Student,</p>
							  
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
							    <mailto='feedback@tellprotocol.com' style='color:inherit; text-decoration:none;'>Feedback</a>	
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
				
				
				Dear Student,
				
				Kindly click on the link below to verify your email address:
				$verMail
				
				OR copy the link and paste into your browser's address bar
				
				This link is valid only for 15minutes.
				
				Best wishes,
				FUTO E-ATTENDANCE Team
				
				
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
	<title>Students SignUp Page | FUTO</title>
	
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
		  
		  <!-- STUDENT EMAIL ADDRESS Element -->
		  <div class="">
		    <label for="usrmail">Email Address</label>
			<div class="input-container" id="input-container">
			  <input autofocus type="email" id="usrmail" name="usrmail" placeholder="Email Address" required value="<?php echo htmlspecialchars($urMail);?>" />
			  <i class="ph-bold ph-at"></i>
			</div>
		    <span class="error" id="usrmailErr"><?php echo htmlspecialchars($urMail_err);?></span><br />
		  </div><br />
		  
		  <!-- MATRIC NO Element -->
		  <div class="">
		    <label for="matricNo">Matric Number</label>
			<div class="input-container" id="input-container">
			  <input type="tel" id="matricNo" name="matricNo" placeholder="Student Matric Number" required value="<?php if($uRegNo === ""){ echo htmlspecialchars($uRegNo); } else { $iniReg = hexdec($uRegNo); echo htmlspecialchars($iniReg); }?>" />
			  <i class="ph-bold ph-student"></i>
			</div>
		    <span class="error" id="uRegNoErr"><?php echo htmlspecialchars($uRegNo_err);?></span><br />
		  </div><br />
		  
		  
		  <!-- Messages -->
		  <div class="messages" id="">
		    <div class="" id="st-error"></div>
		  </div><br />
		  
		  <!-- Submit Button Element -->
		  <button type="submit" id="submitForm" class="" title="Sign Up">SIGN UP</button><br />
		  
		</form>
	  
	  </div>
	</section>
	
	<script type="module">
	  import { FormValidator } from '/dist/js/register.js';
	  
	  const regNoRegex = /^(202[0-6])\d{7}$/;
	  
	  new FormValidator({
		  form: document.querySelector('#signup'),
		  matric: document.querySelector('#matricNo'),
		  email: document.querySelector('#usrmail'),
		  submitBtn: document.querySelector('#submitForm'),
		  matricError: document.querySelector('#uRegNoErr'),
		  emailError: document.querySelector('#usrmailErr'),
		  inputDiv: document.querySelector('#tellInputDiv'),
		  
		  matricRegex: regNoRegex
	  });
	</script>
	<?php echo $jsScript;?><?php echo $jsSnippet;?>
  </body>
</html>