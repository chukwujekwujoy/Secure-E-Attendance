<?php
/**
 * Geolocation helpers for attendance geofencing.
 * No dependencies — pure PHP, matches your no-framework stack.
 */

/**
 * Great-circle distance between two lat/lng points, in meters.
 */
function haversineDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadiusM = 6371000.0;

    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) ** 2
        + cos($lat1Rad) * cos($lat2Rad) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadiusM * $c;
}

/**
 * Validate a lat/lng pair. Returns true if within valid Earth-coordinate bounds.
 */
function isValidCoordinate($lat, $lng): bool
{
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return false;
    }
    $lat = (float) $lat;
    $lng = (float) $lng;
    return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
}

/**
 * Sanity-clamp a client-reported GPS accuracy value so a spoofed/huge
 * accuracy figure can't be used to widen the effective geofence.
 */
function clampAccuracyBuffer($accuracyM, float $maxBufferM = 75.0): float
{
    if (!is_numeric($accuracyM) || $accuracyM < 0) {
        return 0.0;
    }
    return min((float) $accuracyM, $maxBufferM);
}
