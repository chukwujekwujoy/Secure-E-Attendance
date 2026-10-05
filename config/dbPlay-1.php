<?php

/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with password) */

define('DB_SERVER', '');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');
define('DB_NAME', '');
 
/* Attempt to connect to MySQL database using PDO method */
try{
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    /* Set the PDO error mode to exception */
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
	/* Kill script with error message */
    die("ERROR: Error connecting to DB! " . $e->getMessage());
}
?>
