<?php
declare(strict_types=1);

/**
 * Minimal DER encoder — just enough to build a SubjectPublicKeyInfo
 * structure from raw COSE key material so OpenSSL can load it as a
 * PEM public key. No external libraries.
 */
final class Der
{
    public static function length(int $len): string
    {
        if ($len < 0x80) {
            return chr($len);
        }
        $bytes = '';
        while ($len > 0) {
            $bytes = chr($len & 0xFF) . $bytes;
            $len >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    public static function tlv(int $tag, string $value): string
    {
        return chr($tag) . self::length(strlen($value)) . $value;
    }

    public static function sequence(string $value): string
    {
        return self::tlv(0x30, $value);
    }

    public static function bitString(string $value): string
    {
        // Leading 0x00 = zero unused bits.
        return self::tlv(0x03, "\x00" . $value);
    }

    public static function oid(string $dottedOid): string
    {
        $parts = array_map('intval', explode('.', $dottedOid));
        $first = array_shift($parts);
        $second = array_shift($parts);
        $bytes = chr($first * 40 + $second);

        foreach ($parts as $part) {
            if ($part < 0x80) {
                $bytes .= chr($part);
                continue;
            }
            $chunk = '';
            $chunk .= chr($part & 0x7F);
            $part >>= 7;
            while ($part > 0) {
                $chunk = chr(($part & 0x7F) | 0x80) . $chunk;
                $part >>= 7;
            }
            $bytes .= $chunk;
        }
        return self::tlv(0x06, $bytes);
    }

    /** Unsigned integer — prepends 0x00 if the high bit would flip the sign. */
    public static function unsignedInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '' || (ord($bytes[0]) & 0x80)) {
            $bytes = "\x00" . $bytes;
        }
        return self::tlv(0x02, $bytes);
    }

    /** Build a PEM-encoded EC public key (P-256) from raw x, y coordinates. */
    public static function ecPublicKeyPem(string $x, string $y): string
    {
        $idEcPublicKey = self::oid('1.2.840.10045.2.1');
        $prime256v1    = self::oid('1.2.840.10045.3.1.7');
        $algorithm     = self::sequence($idEcPublicKey . $prime256v1);

        $point = "\x04" . $x . $y; // uncompressed point
        $publicKey = self::bitString($point);

        $spki = self::sequence($algorithm . $publicKey);
        return self::pem($spki, 'PUBLIC KEY');
    }

    /** Build a PEM-encoded RSA public key from raw modulus (n) and exponent (e). */
    public static function rsaPublicKeyPem(string $n, string $e): string
    {
        $rsaKey = self::sequence(
            self::unsignedInteger($n) . self::unsignedInteger($e)
        );

        $idRsaEncryption = self::oid('1.2.840.113549.1.1.1');
        $algorithm = self::sequence($idRsaEncryption . "\x05\x00"); // NULL params
        $publicKey = self::bitString($rsaKey);

        $spki = self::sequence($algorithm . $publicKey);
        return self::pem($spki, 'PUBLIC KEY');
    }

    /** Wrap a raw DER-encoded X.509 certificate as PEM. */
    public static function certificatePem(string $der): string
    {
        return self::pem($der, 'CERTIFICATE');
    }

    private static function pem(string $der, string $label): string
    {
        $b64 = base64_encode($der);
        $lines = trim(chunk_split($b64, 64, "\n"));
        return "-----BEGIN {$label}-----\n{$lines}\n-----END {$label}-----\n";
    }
}
