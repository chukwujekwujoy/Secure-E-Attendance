<?php
declare(strict_types=1);

/**
 * WebAuthn / Passkey configuration.
 * Adjust these to match actual domain before going to production.
 */

/* Public hostname used by the browser */
$host = $_SERVER['HTTP_HOST'] ?? '';

/* Remove port if it somehow appears */
$host = preg_replace('/:\d+$/', '', $host);


// Relying Party ID — must be domain (no scheme, no port). For
// localhost testing this can be "localhost".
define('RP_ID', $host);

/* Exact browser origin */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    ? 'https'
    : 'http';

// Human readable name shown in the OS passkey prompt.
define('RP_NAME', 'FUTO E-ATTENDANCE');

// Full origin the browser will send back in clientDataJSON. Must match
// exactly (scheme + host + port).
define('RP_ORIGIN', $scheme . '://' . $_SERVER['HTTP_HOST']);

// How long a registration challenge is valid for, in seconds.
define('CHALLENGE_TTL', 300);

// Database connection (PDO / MySQL). No ORM, no external libs.
define('DB_SERVER', getenv('DB_HOST') ?: 'amwhdi.h.filess.io');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
define('DB_USERNAME', getenv('DB_USER') ?: 'futo_attendance_savetower');
define('DB_PASSWORD', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'futo_attendance_savetower');

/* Attempt to connect to MySQL database using PDO method */
try{
   $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    /* Set the PDO error mode to exception */
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
	/* Kill script with error message */
    die("ERROR: Error connecting to DB! " . $e->getMessage());
}
?>

