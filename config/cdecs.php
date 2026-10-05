<?php

function gencdes() {
	return substr(bin2hex(random_bytes(5)), 0, 9);
}

function gentokes() {
	return substr(bin2hex(random_bytes(16)), 0, 32);
}

function generate_uuidv4() {
	// Generate 16 random bytes (128 bits)
	$data = random_bytes(16);
	
	// Set the version bits (4) for UUIDv4: bits 12-15 of the time_hi_and_version field (byte 7)
	$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	
	// Set the variant bits (RFC 4122 variant): bits 6-7 of the clock_seq_hi_and_reserved field (byte 8)
	$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
	
	// Convert to hex and insert hyphens in the standard UUID format: 8-4-4-4-12
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	
}
function gencos() {
	return dechex(random_int(177889475, 200000000));
}


$cde2 = strtoupper(gencdes());
$redy = implode('-', str_split($cde2, 3));
$tokers = gentokes();
$tokredy = implode('-', str_split($tokers, 4));
$riinv = generate_uuidv4();
$replacement = "ec74df98ca55";
$newString = substr_replace($riinv, $replacement, -12);

$randomNumb = strtoupper(gencos());


?>