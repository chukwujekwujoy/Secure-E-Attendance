<?php

/* Database file */
require_once("../../config/db.php");


$err_msg = $now = $thn = $dif = $trasf = $jsSnippet = $jsScript = "";
$err_msg0 = $err_msg1 = $err_msg2 = $err_msg3 = $err_msg4 = "";
// Validate and sanitize user input from URL parameters
$user = isset($_GET['user']) ? trim($_GET['user']) : "";
$verificationcode = isset($_GET['usercode']) ? trim($_GET['usercode']) : "";
$action = isset($_GET['action']) ? trim($_GET['action']) : "";


try {
	
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
    <meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
	<!-- Responsive Tags -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	
	<!-- Title Tag -->
	<title>Email Verification Page | FUTO</title>
	
	<!-- Phosphor Icons CSS File -->
	<link href="../inc/phosphor-icons/duotone/style.css" rel="stylesheet">
	<link href="../inc/phosphor-icons/bold/style.css" rel="stylesheet">
	
	<!-- Fonts -->
	<link rel="preload" href="../inc/css/fonts/trebuc.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="../inc/css/fonts/trebuc.woff" as="font" type="font/woff" crossorigin>
	<link rel="preload" href="../inc/css/fonts/Alef-Regular.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="../inc/css/fonts/Alef-Regular.woff" as="font" type="font/woff" crossorigin>
	
	<!-- External Font CSS  -->
	<link href="../inc/css/fonts.css" rel="stylesheet">
	
	<!-- External Stylesheet File -->
	<link href="../inc/css/students.css" rel="stylesheet">
	<link href="../inc/css/preloader.css" rel="stylesheet">
	
	<!-- ExternL JS Tags -->
	<script type="text/javascript"></script>
	<script type="text/javascript" src=""></script>
	
	<!-- Site favicon Tag -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico" />
	
	<!-- My Manifest Tag (JSON format) -->
	<link rel="manifest" href="./manifest.json" />
  </head>
  <body>
    
  </body>
</html>