<?php

/* Database credentials — sourced from environment variables,
   injected via Secret Manager in Cloud Run. No secrets in code. */

define('DB_SERVER', getenv('DB_HOST') ?: 'amwhdi.h.filess.io');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
define('DB_USERNAME', getenv('DB_USER') ?: 'futo_attendance_savetower');
define('DB_PASSWORD', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'futo_attendance_savetower');

/* Attempt to connect to MySQL database using PDO method */
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    /* Set the PDO error mode to exception */
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    /* Kill script with error message */
    die("ERROR: Error connecting to DB! " . $e->getMessage());
}
