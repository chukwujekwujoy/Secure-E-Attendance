<?php

/* Database file */
require_once __DIR__ . '../../config/dbPlay.php';


$verifyStatus = $idD1 = $piin = "";
$err_msg = $now = $thn = $dif = $trasf = $jsSnippet = $jsScript = "";
$err_msg0 = $err_msg1 = $err_msg2 = $err_msg3 = $err_msg4 = "";
// Validate and sanitize user input from URL parameters
$user = isset($_GET['user']) ? trim($_GET['user']) : "";
$validity = isset($_GET['nonce']) ? trim($_GET['nonce']) : "";
$verificationcode = isset($_GET['usercode']) ? trim($_GET['usercode']) : "";
$action = isset($_GET['action']) ? trim($_GET['action']) : "";


try {
    
    // Check if user exists in 'users' table
    $sql = "SELECT * FROM usersTemp WHERE userID = :user AND vericodes = :usercode";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt->bindParam(':usercode', $verificationcode, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() === 1) {
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
		$uID = $userData["userID"];
		$usrMaile = $userData["userEmil"];
		$piin = $userData["usrPin"];
		$verifcodes = $userData["vericodes"];
		$eraValid = $userData["eraValid"];
		
        $now = time();
		$timedif = $now - $eraValid;
		if($timedif <= 900){
			// Delete user from 'usersTemp' table
			$deleteSql = "DELETE FROM usersTemp WHERE userID = :user";
			
			$deleteStmt = $pdo->prepare($deleteSql);
			$deleteStmt->bindParam(':user', $user, PDO::PARAM_STR);
			$deleteStmt->execute();
			
			// Insert user into 'users' table
			$insertSql = "INSERT INTO lecturerDetails (userID, userEmil, usrPin) VALUES (:uID, :urMail, :upin)";
			$insertStmt = $pdo->prepare($insertSql);
			$insertStmt->bindParam(':uID', $user, PDO::PARAM_STR);
			$insertStmt->bindParam(':urMail', $usrMaile, PDO::PARAM_STR);
			$insertStmt->bindParam(':upin', $piin, PDO::PARAM_STR);
			$insertStmt->execute();
			
			$err_msg0 = "<div class=''><h2>" . htmlspecialchars("ACCOUNT VERIFICATION SUCCESSFUL!") . "</h2><span class=''>" . htmlspecialchars("Redirecting you to sign-in page for proper sign-in in 4 seconds") . "</span></div>";
			$jsSnippet = <<<EOD
			<script type="text/javascript">
			  document.querySelector("#veryfyInfo").innerHTML = "$err_msg0";
			  setTimeout(()=>{window.location.href = "./signin.php";}, 3000);
			</script>
			EOD;
		} else{
			// Delete user from 'usersTemp' table
			$deleteSql = "DELETE FROM usersTemp WHERE userID = :user";
			
			$deleteStmt = $pdo->prepare($deleteSql);
			$deleteStmt->bindParam(':user', $user, PDO::PARAM_STR);
			$deleteStmt->execute();
			
			$err_msg1 = "<div class=''><h2>" . htmlspecialchars("INVALID VERIFICATION LINK") . "</h2><span class=''>" . htmlspecialchars("This verification link is expired!") . "<br />" . htmlspecialchars("Redirecting you to sign up page for registration") . "</span></div>";
			$jsSnippet = <<<EOD
			<script type="text/javascript">
			  document.querySelector("#veryfyInfo").innerHTML = "$err_msg1";
			  setTimeout(()=>{window.location.href = "./signup.php";}, 10000);
			</script>
			EOD;
		}
        
    } else {
        $err_msg2 = "Invalid verification link!!";
		$jsScript = <<<EOD
		<script type="text/javascript">
		  alert("$err_msg2");
		  window.location.href = "./signup.php";
		</script>
		EOD;
    }
} catch (PDOException $e) {
	$err_msg3 = "Database error: " . htmlspecialchars($e->getMessage());
	$jsSnippet = <<<EOD
	<script type="text/javascript">alert("$err_msg3");</script>
	EOD;
} catch (Exception $e) {
	$err_msg4 = "An error occurred: " . htmlspecialchars($e->getMessage());
	$jsSnippet = <<<EOD
	<script type="text/javascript">alert("$err_msg4");</script>
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
	<title>Email Verification Page | FUTO</title>
	
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
	
	<!-- ExternL JS Tags -->
	<script type="text/javascript" src="">
	  
	</script>
	
	<!-- Site favicon Tag -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico" />
	
	<!-- My Manifest Tag (JSON format) -->
	<link rel="manifest" href="./manifest.json" />
  </head>
  <body>
    
    <!-- Passkey Authentication dialog -->
	<dialog class="" id="" name=""></dialog>
	
	<section class="root" id="root" title="Confirm Email Form">
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
		
		<div class="" title="Input Your Email Address">
		  <label for="usrmail">Email Address</label>
		  <div class="input-container" id="input-container">
		    <input type="text" id="usrmail" autofocus readonly name="tutorMail" placeholder="Email Address" required value="<?php echo htmlspecialchars($user);?>" />
			<i class="ph-bold ph-at"></i>
		  </div>
		</div><br />
		
		<!-- Messages -->
		<div class="messages" id="">
		  <div class="" id="veryfyInfo"></div><br />
		</div>
	  
	  
	</section>
	<?php echo $jsScript;?><?php echo $jsSnippet;?>
  </body>
</html>