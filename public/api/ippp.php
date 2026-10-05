<?php

$remAdd = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$xForwa = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Not available';
$clieIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'Not available';

echo htmlspecialchars($remAdd, ENT_QUOTES, 'UTF-8') . '<br />';
echo htmlspecialchars($xForwa, ENT_QUOTES, 'UTF-8') . '<br />';
echo htmlspecialchars($clieIp, ENT_QUOTES, 'UTF-8') . '<br />';

?>