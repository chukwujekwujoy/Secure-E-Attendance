<?php

// mail head
require_once('../config/mailhead.php');
// db head
require_once('../config/dbPlay.php');
// tokens generator
require_once('../config/cdecs.php');



$uID = $urMail = $now = "";
$urMail_err = "";
$msg = $verily = $tokes = "";
$jowin_err = $jowin_erra = $jowin_errb = $jowin_errc = $jowin_errd = $jowin_erre = $jowin_errf = $jowin_errg = '';
$jsSnippet = $jsScript = "";

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
		$sql = 'SELECT usrMaile FROM userLog WHERE usrMaile = :maile';

		if($stmt = $pdo->prepare($sql)){
			$param_maile = trim($_POST['usrmail']);
			$stmt->bindParam(":maile", $param_maile, PDO::PARAM_STR);
			if($stmt->execute()){
				
				if($stmt->fetch()){
					$urMail = trim($_POST['usrmail']);
				} else {
					$urMail_err = "Invalid Email Address!";
					
					$jsSnippet = <<<EOD
					<script type="text/javascript">
					  document.querySelector("#sl-email").focus();
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
		
	// Verification Token handler
	$tokes = generate_uuidv4();
	
	
	
	if(empty($urMail_err) && empty($jowin_err) && !empty($tokes)){
		
		$sql = 'SELECT usrMaile, usrID, usrGwam FROM userLog WHERE usrMaile = :maile';
		
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':maile', $urMail, PDO::PARAM_STR);
		
		$stmt->execute();
		if($stmt->rowCount() === 1) {
			$userData = $stmt->fetch(PDO::FETCH_ASSOC);
			$uID = $userData["usrID"];
			$userEmail = $userData["usrMaile"];
			$userPass = $userData["usrGwam"];
			$ugbua = time();
			
			$insertSql = 'INSERT INTO userTemp (usrID, usrMaile, usrGwam, vericodes, eraValid) VALUES (:uID, :maile, :piin, :vercode, :ugbua)';
			
			$insertStmt = $pdo->prepare($insertSql);
			$insertStmt->bindParam(':uID', $uID, PDO::PARAM_STR);
			$insertStmt->bindParam(':maile', $userEmail, PDO::PARAM_STR);
			$insertStmt->bindParam(':piin', $userPass, PDO::PARAM_STR);
			$insertStmt->bindParam(':vercode', $tokes, PDO::PARAM_STR);
			$insertStmt->bindParam(':ugbua', $ugbua, PDO::PARAM_STR);
			
			$insertStmt->execute();
			
			
			$verily = "https://nikkidapp.iceiy.com/confirm-email.php?user=" . $uID . "&nonce=" . $ugbua . "&usercode=" . $tokes . "&action=reset-password";
			
			
			// Mailing Agent
			$mail->setFrom("tellprotocol@gmail.com", "NIKKI DApp");
			$mail->addAddress($urMail);
			$mail->Subject = "Change Password";
			$mail->isHTML(true);
			$mail->Body = "<!DOCTYPE html>
			
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
							  <h1 class='text' style='margin:0; color:#24292f;'>NIKKI DApp</h1>
							</td>
						  </tr>
						  
						  <!-- CONTENT -->
						  <tr>
						    <td class='padding text' style='padding:30px; color:#24292f; font-size:15px; line-height:1.6;'>
							  
							  <p>Dear $userEmail,</p>
							  
							  <p>Kindly click on the link below to verify your email address:</p>
							  
							  <!-- BUTTON -->
							  <table class='button' width='100%' cellpadding='0' cellspacing='0' style='margin:25px 0;'>
							    <tr>
								  <td align='center'>
								    <a href='$verily' style='background:#2ea44f; color:#ffffff; text-decoration:none; padding:14px 24px; border-radius:6px; font-weight:bold; display:inline-block;'>Verify your Email</a>
								  </td>
								</tr>
							  </table>
							  
							  <p>OR copy the link below and paste into your browser's address bar</p>
							  <p>
							    <a href='$verily'
style='color:#2ea44f; text-decoration:none; font-weight:bold;'>$verily</a>
							  </p>
							  
							  <p style='font-weight:bold;'>
							    The link is valid only for 15minutes.
							  </p><br />
							  
							  <p style='font-weight:bold;'>
							    If you do not recognise this signin attempt kindly disregard this mail and tke more steps to securing your account! 
							  </p>
							  
							</td>
						  </tr>
						  
						  <!-- FOOTER -->
						  <tr>
						    <td class='muted' style='padding:20px; text-align:center; font-size:12px; color:#6a737d;'>
							  <p>
							    <a href='x.com/@JustAkudike' style='color:inherit; text-decoration:none;'>X (Formerly Twitter)</a>|
							    <a href='saxifragejasper@gmail.com' style='color:inherit; text-decoration:none;'>Feedback</a>	
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
			NIKKI Dapp
			
			Dear $userEmail,
			
			Kindly copy the link below and paste into your browser's address bar:
			$verily
			The link is valid only for 15minutes.
			
			If you do not recognise this signin attempt kindly disregard this mail and tke more steps to securing your account!
			
			Best wishes,
			The NIKKI Dapp Team
			";
			
			if ($mail->send()) {
				$jsSnippet = <<<EOD
				<script type="text/javascript">alert("A password reset link has been sent to your provided email address. Kindly check your email and follow the instructions to reset your password.")</script>
				EOD;
			} else {
				echo $mail->ErrorInfo;
			}
		} else {
			$urMail_err = htmlspecialchars("Invalid Email Address!");
			$jsSnippet = <<<EOD
			  <script type="text/javascript">
			    document.querySelector("#sl-usrmail").focus();
			  </script>
			EOD;
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
	<link href="../dist/phosphor-icons/duotone/style.css" rel="stylesheet">
	<link href="../dist/phosphor-icons/bold/style.css" rel="stylesheet">
	
	<!-- Peload Fonts File -->
	<link rel="preload" href="../dist/css/fonts/trebuc.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="../dist/css/fonts/trebuc.woff" as="font" type="font/woff" crossorigin>
	<link rel="preload" href="../dist/css/fonts/Alef-Regular.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="../dist/css/fonts/Alef-Regular.woff" as="font" type="font/woff" crossorigin>
	
	<!-- External Font CSS  -->
	<link href="../dist/css/fonts.css" rel="stylesheet">
	
	<!-- External Stylesheet File -->
	<link href="../dist/css/students.css" rel="stylesheet">
	<link href="../dist/css/preloader.css" rel="stylesheet">
	
	<link href="" rel="stylesheet">
	<link href="" rel="stylesheet">
		
	<!-- Site favicon Tag -->
	<link rel="icon" type="image/x-icon" href="../../favicon.ico" />
	
	<!-- My Manifest Tag (JSON format) -->
	<link rel="manifest" href="./manifest.json" />
  </head>
  <body>
    
	
    <!-- Passkey Authentication dialog -->
	<dialog class="" id="" name=""></dialog>
	
	<section class="root" id="root" title="Attendance Form">
	  <!-- Default view: Sign In -->
	  <div class="auth-card" id="">
		
		<!-- Form Element -->
		<form id="forgottenPassword" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
		  
		  <!-- LECTURER EMAIL ADDRESS Element -->
		  <div class="" title="Input Your School Matric No">
		    <label for="usrmail">Email Address</label>
			<div class="input-container" id="input-container">
			  <input type="email" id="usrmail" autofocus name="usrmail" placeholder="Email Address" required value="<?php echo htmlspecialchars($urMail);?>" />
			  <i class="ph-bold ph-at"></i>
			</div>
		    <span class="error" id="usrmailErr"><?php echo htmlspecialchars($urMail_err);?></span><br />
		  </div>
		  
		  
		  <!-- Messages -->
		  <div class="messages" id="">
		    <div class="" id="st-error"></div>
		  </div><br /><br />
		  
		  <!-- Submit Button Element -->
		  <button type="submit" id="submitForm" class="" title="Submit Attendance">SIGN UP</button>
		  
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
	  	  
	  
	  new FormValidator({
		  form: document.getElementById('forgottenPassword'),
		  email: document.getElementById('usrmail'),
		  submitBtn: document.getElementById('submitForm'),
		  emailError: document.getElementById('usrmailErr'),
		  inputDiv: document.getElementById('tellInputDiv'),
		  
	  });
	  
	</script>
	<?php echo $jsScript;?><?php echo $jsSnippet;?>
  </body>
</html>