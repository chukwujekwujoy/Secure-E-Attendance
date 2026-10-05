<?php

function getDomain(bool $stripWww = false): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // remove port
    $host = strtok($host, ':');

    // optionally remove www.
    if ($stripWww) {
        $host = preg_replace('/^www\./', '', $host);
    }

    return $host;
}


?>