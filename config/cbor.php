<?php
declare(strict_types=1);

require_once __DIR__ . '/bytes.php';

/**
 * Minimal CBOR decoder — just enough of RFC 8949 to parse WebAuthn
 * attestationObjects and COSE public keys. No external libraries.
 *
 * Supports: unsigned int, negative int, byte string, text string,
 * array, map, simple values (true/false/null), and the two float
 * widths COSE never actually uses (skipped defensively).
 */
final class Cbor
{
    public static function decode(string $data, int &$offset = 0)
    {
        $initial = ord(read_bytes($data, $offset, 1));
        $majorType = $initial >> 5;
        $additional = $initial & 0x1F;

        $length = self::readLength($data, $offset, $additional);

        switch ($majorType) {
            case 0: // unsigned int
                return $length;

            case 1: // negative int: value = -1 - n
                return -1 - $length;

            case 2: // byte string
                return read_bytes($data, $offset, $length);

            case 3: // text string
                return read_bytes($data, $offset, $length);

            case 4: // array
                $items = [];
                for ($i = 0; $i < $length; $i++) {
                    $items[] = self::decode($data, $offset);
                }
                return $items;

            case 5: // map
                $map = [];
                for ($i = 0; $i < $length; $i++) {
                    $key = self::decode($data, $offset);
                    $val = self::decode($data, $offset);
                    $map[$key] = $val;
                }
                return $map;

            case 6: // tagged value — decode and return the tagged item
                return self::decode($data, $offset);

            case 7: // simple / float
                if ($additional === 20) return false;
                if ($additional === 21) return true;
                if ($additional === 22) return null;
                if ($additional === 25) { // half float — not used by COSE keys we need
                    read_bytes($data, $offset, 2);
                    return null;
                }
                if ($additional === 26) {
                    $bytes = read_bytes($data, $offset, 4);
                    return unpack('G', $bytes)[1];
                }
                if ($additional === 27) {
                    $bytes = read_bytes($data, $offset, 8);
                    return unpack('E', $bytes)[1];
                }
                return null;

            default:
                throw new RuntimeException('Unsupported CBOR major type: ' . $majorType);
        }
    }

    private static function readLength(string $data, int &$offset, int $additional): int
    {
        if ($additional <= 23) {
            return $additional;
        }
        if ($additional === 24) {
            return ord(read_bytes($data, $offset, 1));
        }
        if ($additional === 25) {
            return read_uint16_be($data, $offset);
        }
        if ($additional === 26) {
            return read_uint32_be($data, $offset);
        }
        if ($additional === 27) {
            // 64-bit length — fine for our sizes, just read as two 32-bit halves
            $hi = read_uint32_be($data, $offset);
            $lo = read_uint32_be($data, $offset);
            return ($hi << 32) | $lo;
        }
        throw new RuntimeException('Indefinite-length CBOR items are not supported');
    }
}
