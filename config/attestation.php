<?php
declare(strict_types=1);

require_once __DIR__ . '/der.php';

/**
 * Verifies WebAuthn attestation statements and derives a best-effort
 * device fingerprint from the leaf attestation certificate, when one
 * is available.
 *
 * IMPORTANT LIMITATIONS (read before relying on this for security
 * decisions):
 *
 *  - "packed" (with or without an x5c cert chain) and "fido-u2f" are
 *    implemented. "apple", "android-safetynet", "android-key" and
 *    "tpm" are deliberately NOT implemented and will throw — silently
 *    accepting them would misrepresent what was actually verified.
 *  - "none" attestation and "packed" self-attestation (no x5c) are
 *    ACCEPTED. This is standard for passkey deployments: most platform
 *    authenticators (Touch ID, Android fingerprint/face unlock) return
 *    "none" by OS-level privacy policy regardless of the RP's
 *    requested attestation conveyance, and self-attestation only
 *    proves the authenticator signed with the credential's own key —
 *    neither gives a certificate-derived device identity signal.
 *    In both cases 'deviceFingerprint' is returned as null; the
 *    caller (register_verify.php) is expected to fall back to its own
 *    application-level device identifier for device-binding /
 *    one-passkey-per-device enforcement.
 *  - When a certificate chain IS present (hardware security keys,
 *    TPM-backed Windows Hello), the leaf certificate is NOT validated
 *    against a trust root (that requires the FIDO Metadata Service, a
 *    separate integration). We only verify the attestation signature
 *    is cryptographically valid, and use the leaf cert hash as a
 *    fingerprint.
 *  - Some authenticator models use "batch attestation" — many physical
 *    units sharing one certificate. Even a cert-derived fingerprint is
 *    therefore a strong heuristic for device identity, not a
 *    guarantee.
 */
final class WebAuthnAttestation
{
    /**
     * @return array{deviceFingerprint:?string, format:string, certChainPem:string[]}
     */
    public static function verify(
        string $fmt,
        array $attStmt,
        string $authData,
        string $clientDataHash,
        string $rpIdHash,
        string $credentialId,
        array $coseKey
    ): array {
        switch ($fmt) {
            case 'packed':
                return self::verifyPacked($attStmt, $authData, $clientDataHash, $coseKey);

            case 'fido-u2f':
                return self::verifyFidoU2f($attStmt, $clientDataHash, $rpIdHash, $credentialId, $coseKey);

            case 'none':
                // No attestation statement at all — expected from most
                // platform authenticators (Touch ID, Android fingerprint/
                // face unlock) due to OS-level privacy policy, regardless
                // of the 'attestation' conveyance we requested. Nothing
                // cryptographic to verify here beyond what
                // verifyClientData()/parseAuthenticatorData() already
                // checked. No device-identifying signal is available.
                return [
                    'deviceFingerprint' => null,
                    'format' => 'none',
                    'certChainPem' => [],
                ];

            default:
                throw new WebAuthnException(
                    "Attestation format '{$fmt}' is not supported. Only 'packed', " .
                    "'fido-u2f' and 'none' are currently handled."
                );
        }
    }

    private static function verifyPacked(array $attStmt, string $authData, string $clientDataHash, array $coseKey): array
    {
        $sig = $attStmt['sig'] ?? null;
        $x5c = $attStmt['x5c'] ?? null;

        if (!is_string($sig)) {
            throw new WebAuthnException('Malformed packed attestation: missing signature');
        }

        $signedData = $authData . $clientDataHash;

        if (is_array($x5c) && count($x5c) > 0 && is_string($x5c[0])) {
            // Full attestation via a device certificate — a real device
            // identity signal (modulo batch attestation, see class docblock).
            $leafDer = $x5c[0];
            $leafPem = Der::certificatePem($leafDer);

            $ok = openssl_verify($signedData, $sig, $leafPem, OPENSSL_ALGO_SHA256);
            if ($ok !== 1) {
                throw new WebAuthnException('Packed attestation signature verification failed');
            }

            return [
                'deviceFingerprint' => hash('sha256', $leafDer),
                'format' => 'packed',
                'certChainPem' => array_map(
                    static fn($der) => Der::certificatePem($der),
                    array_filter($x5c, 'is_string')
                ),
            ];
        }

        // Self-attestation: no certificate chain. Signed directly with the
        // credential's own private key (the same key whose public half is
        // in $coseKey). This proves the authenticator holds that key, not
        // which physical device it is — no fingerprint available.
        $pem = self::coseKeyToPem($coseKey);
        $ok = openssl_verify($signedData, $sig, $pem, self::algoFor($coseKey));
        if ($ok !== 1) {
            throw new WebAuthnException('Self-attested packed signature verification failed');
        }

        return [
            'deviceFingerprint' => null,
            'format' => 'packed-self',
            'certChainPem' => [],
        ];
    }

    private static function verifyFidoU2f(
        array $attStmt,
        string $clientDataHash,
        string $rpIdHash,
        string $credentialId,
        array $coseKey
    ): array {
        $sig = $attStmt['sig'] ?? null;
        $x5c = $attStmt['x5c'] ?? null;

        if (!is_string($sig)) {
            throw new WebAuthnException('Malformed fido-u2f attestation: missing signature');
        }
        if (!is_array($x5c) || count($x5c) === 0 || !is_string($x5c[0])) {
            // The fido-u2f format has no self-attestation variant in the
            // spec — a certificate is mandatory here.
            throw new WebAuthnException('fido-u2f attestation is missing its certificate');
        }

        $x = $coseKey[-2] ?? null;
        $y = $coseKey[-3] ?? null;
        if (!is_string($x) || !is_string($y)) {
            throw new WebAuthnException('fido-u2f attestation requires an EC2 (P-256) credential key');
        }

        $leafDer = $x5c[0];
        $leafPem = Der::certificatePem($leafDer);

        // U2F raw registration response signature base, per the
        // fido-u2f attestation statement format spec:
        //   0x00 || rpIdHash || clientDataHash || credentialId || publicKeyU2F
        $publicKeyU2F = "\x04" . $x . $y; // uncompressed EC point
        $signedData = "\x00" . $rpIdHash . $clientDataHash . $credentialId . $publicKeyU2F;

        $ok = openssl_verify($signedData, $sig, $leafPem, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            throw new WebAuthnException('fido-u2f attestation signature verification failed');
        }

        return [
            'deviceFingerprint' => hash('sha256', $leafDer),
            'format' => 'fido-u2f',
            'certChainPem' => [$leafPem],
        ];
    }

    /** Build a PEM public key from a COSE_Key map, for verifying self-attestation. */
    private static function coseKeyToPem(array $coseKey): string
    {
        $kty = $coseKey[1] ?? null; // 1 = kty

        if ($kty === 2) { // EC2
            $x = $coseKey[-2] ?? null;
            $y = $coseKey[-3] ?? null;
            if (!is_string($x) || !is_string($y)) {
                throw new WebAuthnException('Malformed EC2 COSE key in self-attestation');
            }
            return Der::ecPublicKeyPem($x, $y);
        }

        if ($kty === 3) { // RSA
            $n = $coseKey[-1] ?? null;
            $e = $coseKey[-2] ?? null;
            if (!is_string($n) || !is_string($e)) {
                throw new WebAuthnException('Malformed RSA COSE key in self-attestation');
            }
            return Der::rsaPublicKeyPem($n, $e);
        }

        throw new WebAuthnException('Unsupported COSE key type in self-attestation: ' . var_export($kty, true));
    }

    /** Map a COSE alg id (map key 3) to the matching OpenSSL digest algo constant. */
    private static function algoFor(array $coseKey): int
    {
        $alg = $coseKey[3] ?? null;
        return match ($alg) {
            -7, -257 => OPENSSL_ALGO_SHA256, // ES256, RS256
            default => throw new WebAuthnException('Unsupported COSE alg in self-attestation: ' . var_export($alg, true)),
        };
    }
}