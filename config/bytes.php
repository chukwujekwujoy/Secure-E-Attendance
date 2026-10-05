<?php
declare(strict_types=1);

/**
 * Base64url + raw byte helpers used throughout the WebAuthn flow.
 * WebAuthn/JS sends binary data as base64url (RFC 4648 §5) — no padding.
 */

function b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string
{
    $padded = str_pad($data, strlen($data) + (4 - strlen($data) % 4) % 4, '=');
    $decoded = base64_decode(strtr($padded, '-_', '+/'), true);
    if ($decoded === false) {
        throw new RuntimeException('Invalid base64url input');
    }
    return $decoded;
}

/** Read $len bytes from $buf at $offset, advancing $offset. */
function read_bytes(string $buf, int &$offset, int $len): string
{
    if ($offset + $len > strlen($buf)) {
        throw new RuntimeException('Unexpected end of buffer');
    }
    $out = substr($buf, $offset, $len);
    $offset += $len;
    return $out;
}

function read_uint16_be(string $buf, int &$offset): int
{
    $bytes = read_bytes($buf, $offset, 2);
    return unpack('n', $bytes)[1];
}

function read_uint32_be(string $buf, int &$offset): int
{
    $bytes = read_bytes($buf, $offset, 4);
    return unpack('N', $bytes)[1];
}
