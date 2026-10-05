<?php

require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
require __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer();
$mail->isSMTP();
$mail->SMTPDebug = SMTP::DEBUG_SERVER;
$mail->Host = "smtp.gmail.com";
$mail->SMTPAuth = true;
$mail->Username = getenv('GMAIL_USER');
$mail->Password = getenv('GMAIL_APP_PASSWORD');
$mail->SMTPSecure = "tls";
$mail->Port = 587;


?>
