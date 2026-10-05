<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bytes.php';
require_once __DIR__ . '/cbor.php';
require_once __DIR__ . '/der.php';
require_once __DIR__ . '/attestation.php';

/** Thrown on any registration validation failure. */
class WebAuthnException extends RuntimeException {}

final class WebAuthn
{
    // COSE algorithm identifiers we accept.
    private const COSE_ALG_ES256 = -7;
    private const COSE_ALG_RS256 = -257;

    /**
     * Build the PublicKeyCredentialCreationOptions payload sent to the
     * browser's navigator.credentials.create() call.
     *
     * @param string $userHandle Opaque random bytes identifying the user
     *                            (NOT the username — never PII).
     */
    public static function buildRegistrationOptions(
        string $username,
        string $userHandle,
        string $displayName,
        array $excludeCredentialIds = []
    ): array {
        $challenge = random_bytes(32);

        $excludeCredentials = array_map(
            static fn(string $id) => [
                'id'   => b64url_encode($id),
                'type' => 'public-key',
            ],
            $excludeCredentialIds
        );

        return [
            'challenge' => b64url_encode($challenge),
            'rp' => [
                'id'   => RP_ID,
                'name' => RP_NAME,
            ],
            'user' => [
                'id'          => b64url_encode($userHandle),
                'name'        => $username,
                'displayName' => $displayName,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => self::COSE_ALG_ES256], // ES256, preferred
                ['type' => 'public-key', 'alg' => self::COSE_ALG_RS256], // RS256, fallback
            ],
            'authenticatorSelection' => [
                'residentKey'      => 'required', // discoverable credential (passkey)
                'userVerification' => 'required',
            ],
            // Request full device attestation. Note: some authenticators/
            // browsers will still return fmt "none" or omit the cert
            // chain regardless of this setting (platform privacy
            // policies) — verifyRegistration() rejects those.
            'attestation' => 'direct',
            'timeout' => CHALLENGE_TTL * 1000,
            // raw challenge bytes, kept server-side only, not sent back out again
            '_challengeRaw' => $challenge,
        ];
    }

    /**
     * Verify a navigator.credentials.create() response and return the
     * parsed credential ready to persist.
     *
     * @param array  $credential  Decoded JSON body from the browser
     *                            (id, rawId, type, response{clientDataJSON, attestationObject})
     * @param string $expectedChallenge Raw challenge bytes issued earlier
     * @return array{credentialId:string, publicKeyPem:string, coseAlg:int, signCount:int, aaguid:string}
     */
    public static function verifyRegistration(array $credential, string $expectedChallenge): array
    {
        if (($credential['type'] ?? null) !== 'public-key') {
            throw new WebAuthnException('Unexpected credential type');
        }

        $clientDataJSON = b64url_decode($credential['response']['clientDataJSON'] ?? '');
        $attestationObj  = b64url_decode($credential['response']['attestationObject'] ?? '');

        $clientData = self::verifyClientData($clientDataJSON, $expectedChallenge);

        $offset = 0;
        $attestation = Cbor::decode($attestationObj, $offset);

        $fmt     = $attestation['fmt'] ?? '';
        $authData = $attestation['authData'] ?? '';
        if (!is_string($authData)) {
            throw new WebAuthnException('Malformed attestationObject: missing authData');
        }

        $parsed = self::parseAuthenticatorData($authData);

        // rpIdHash must equal SHA-256 of the RP ID we expect.
        if (!hash_equals(hash('sha256', RP_ID, true), $parsed['rpIdHash'])) {
            throw new WebAuthnException('rpIdHash mismatch — wrong RP ID');
        }

        // Bit 0: User Present. Bit 2: User Verified. Bit 6: Attested Credential Data included.
        $flags = $parsed['flags'];
        if (!($flags & 0x01)) {
            throw new WebAuthnException('User Present flag not set');
        }
        if (!($flags & 0x40)) {
            throw new WebAuthnException('No attested credential data in authenticator response');
        }
        if (!($flags & 0x04)) {
            throw new WebAuthnException('User Verified flag not set');
        }

        if ($parsed['credentialId'] === null || $parsed['coseKey'] === null) {
            throw new WebAuthnException('Missing credential ID or public key');
        }

        $attStmt = is_array($attestation['attStmt'] ?? null) ? $attestation['attStmt'] : [];
        $clientDataHash = hash('sha256', $clientDataJSON, true);

        $attResult = WebAuthnAttestation::verify(
            (string)$fmt,
            $attStmt,
            $authData,
            $clientDataHash,
            $parsed['rpIdHash'],
            $parsed['credentialId'],
            $parsed['coseKey']
        );

        [$publicKeyPem, $coseAlg] = self::coseKeyToPem($parsed['coseKey']);

        return [
            'credentialId'      => $parsed['credentialId'],
            'publicKeyPem'      => $publicKeyPem,
            'coseAlg'           => $coseAlg,
            'signCount'         => $parsed['signCount'],
            'aaguid'            => $parsed['aaguid'],
            'deviceFingerprint' => $attResult['deviceFingerprint'],
            'attestationFormat' => $attResult['format'],
        ];
    }

    /**
     * Build the PublicKeyCredentialRequestOptions payload sent to the
     * browser's navigator.credentials.get() call, for signing in with an
     * already-registered passkey.
     *
     * @param string[] $allowCredentialIds Raw credential ID bytes for every
     *                                      passkey registered to this user.
     *                                      An empty array lets the browser
     *                                      surface any discoverable
     *                                      credential for this RP instead
     *                                      of scoping to a specific list.
     */
    public static function buildAuthenticationOptions(array $allowCredentialIds = []): array
    {
        $challenge = random_bytes(32);

        $allowCredentials = array_map(
            static fn(string $id) => [
                'id'   => b64url_encode($id),
                'type' => 'public-key',
            ],
            $allowCredentialIds
        );

        return [
            'challenge'        => b64url_encode($challenge),
            'rpId'             => RP_ID,
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'required',
            'timeout'          => CHALLENGE_TTL * 1000,
            // raw challenge bytes, kept server-side only, not sent back out again
            '_challengeRaw'    => $challenge,
        ];
    }

    /**
     * Verify a navigator.credentials.get() response against a single
     * stored credential (looked up by the caller beforehand) and return
     * the new signature counter to persist.
     *
     * @param array  $credential         Decoded JSON body from the browser
     *                                   (id, rawId, type,
     *                                   response{clientDataJSON, authenticatorData, signature, userHandle})
     * @param string $expectedChallenge  Raw challenge bytes issued earlier
     * @param string $storedPublicKeyPem PEM public key on file for this credential
     * @param int    $storedCoseAlg      COSE alg id on file for this credential
     * @param int    $storedSignCount    Signature counter on file for this credential
     * @return array{credentialId:string, signCount:int}
     */
    public static function verifyAuthentication(
        array $credential,
        string $expectedChallenge,
        string $storedPublicKeyPem,
        int $storedCoseAlg,
        int $storedSignCount
    ): array {
        if (($credential['type'] ?? null) !== 'public-key') {
            throw new WebAuthnException('Unexpected credential type');
        }

        $clientDataJSON = b64url_decode($credential['response']['clientDataJSON'] ?? '');
        $authData       = b64url_decode($credential['response']['authenticatorData'] ?? '');
        $sig            = b64url_decode($credential['response']['signature'] ?? '');

        $clientData = json_decode($clientDataJSON, true);
        if (!is_array($clientData)) {
            throw new WebAuthnException('Malformed clientDataJSON');
        }
        if (($clientData['type'] ?? null) !== 'webauthn.get') {
            throw new WebAuthnException('Unexpected clientData.type');
        }

        $sentChallenge = b64url_decode($clientData['challenge'] ?? '');
        if (!hash_equals($expectedChallenge, $sentChallenge)) {
            throw new WebAuthnException('Challenge mismatch');
        }
        if (($clientData['origin'] ?? null) !== RP_ORIGIN) {
            throw new WebAuthnException('Origin mismatch');
        }

        if ($authData === '') {
            throw new WebAuthnException('Malformed assertion: missing authenticatorData');
        }
        if (!is_string($sig) || $sig === '') {
            throw new WebAuthnException('Malformed assertion: missing signature');
        }

        // Assertion authenticatorData has the same rpIdHash/flags/signCount
        // prefix as registration authData; attested credential data (bit
        // 0x40) is never present here, so this reuses the same parser and
        // simply leaves aaguid/credentialId/coseKey null.
        $parsed = self::parseAuthenticatorData($authData);

        if (!hash_equals(hash('sha256', RP_ID, true), $parsed['rpIdHash'])) {
            throw new WebAuthnException('rpIdHash mismatch — wrong RP ID');
        }

        // Bit 0: User Present. Bit 2: User Verified.
        $flags = $parsed['flags'];
        if (!($flags & 0x01)) {
            throw new WebAuthnException('User Present flag not set');
        }
        if (!($flags & 0x04)) {
            throw new WebAuthnException('User Verified flag not set');
        }

        $algo = match ($storedCoseAlg) {
            self::COSE_ALG_ES256, self::COSE_ALG_RS256 => OPENSSL_ALGO_SHA256,
            default => throw new WebAuthnException('Unsupported stored COSE alg: ' . $storedCoseAlg),
        };

        $clientDataHash = hash('sha256', $clientDataJSON, true);
        $signedData = $authData . $clientDataHash;

        $ok = openssl_verify($signedData, $sig, $storedPublicKeyPem, $algo);
        if ($ok !== 1) {
            throw new WebAuthnException('Assertion signature verification failed');
        }

        $newSignCount = $parsed['signCount'];

        // Anti-cloning check (WebAuthn §7.2 step 21): once either side has
        // ever reported a nonzero counter, the new value must strictly
        // increase. Authenticators that never increment — many platform
        // authenticators legitimately report 0 on every use — are
        // exempted from this, or every one of them would be flagged as
        // "cloned" on their second sign-in.
        if (($storedSignCount !== 0 || $newSignCount !== 0) && $newSignCount <= $storedSignCount) {
            throw new WebAuthnException('Signature counter did not increase — possible cloned authenticator');
        }

        return [
            'credentialId' => b64url_decode((string)($credential['rawId'] ?? '')),
            'signCount'    => $newSignCount,
        ];
    }

    private static function verifyClientData(string $clientDataJSON, string $expectedChallenge): array
    {
        $clientData = json_decode($clientDataJSON, true);
        if (!is_array($clientData)) {
            throw new WebAuthnException('Malformed clientDataJSON');
        }

        if (($clientData['type'] ?? null) !== 'webauthn.create') {
            throw new WebAuthnException('Unexpected clientData.type');
        }

        $sentChallenge = b64url_decode($clientData['challenge'] ?? '');
        if (!hash_equals($expectedChallenge, $sentChallenge)) {
            throw new WebAuthnException('Challenge mismatch');
        }

        if (($clientData['origin'] ?? null) !== RP_ORIGIN) {
            throw new WebAuthnException('Origin mismatch');
        }

        return $clientData;
    }

    /**
     * Parse the authenticatorData byte layout (WebAuthn §6.1):
     *   rpIdHash(32) | flags(1) | signCount(4) | [attestedCredentialData] | [extensions]
     */
    private static function parseAuthenticatorData(string $authData): array
    {
        $offset = 0;
        $rpIdHash = read_bytes($authData, $offset, 32);
        $flags    = ord(read_bytes($authData, $offset, 1));
        $signCount = read_uint32_be($authData, $offset);

        $aaguid = null;
        $credentialId = null;
        $coseKey = null;

        if ($flags & 0x40) { // Attested Credential Data present
            $aaguid = read_bytes($authData, $offset, 16);
            $credIdLen = read_uint16_be($authData, $offset);
            $credentialId = read_bytes($authData, $offset, $credIdLen);

            // The COSE public key is the remainder of a CBOR item starting
            // here; Cbor::decode will advance $offset exactly past it,
            // leaving any trailing extensions bytes untouched.
            $coseKey = Cbor::decode($authData, $offset);
        }

        return compact('rpIdHash', 'flags', 'signCount', 'aaguid', 'credentialId', 'coseKey');
    }

    /**
     * Convert a decoded COSE_Key map into a PEM public key + the COSE alg id.
     * Supports EC2 (P-256, ES256) and RSA (RS256) keys.
     */
    private static function coseKeyToPem(array $coseKey): array
    {
        $kty = $coseKey[1] ?? null; // 1 = kty
        $alg = $coseKey[3] ?? null; // 3 = alg

        if ($kty === 2) { // EC2
            $crv = $coseKey[-1] ?? null;
            $x   = $coseKey[-2] ?? null;
            $y   = $coseKey[-3] ?? null;
            if ($crv !== 1 || !is_string($x) || !is_string($y)) { // 1 = P-256
                throw new WebAuthnException('Unsupported or malformed EC2 COSE key');
            }
            return [Der::ecPublicKeyPem($x, $y), self::COSE_ALG_ES256];
        }

        if ($kty === 3) { // RSA
            $n = $coseKey[-1] ?? null;
            $e = $coseKey[-2] ?? null;
            if (!is_string($n) || !is_string($e)) {
                throw new WebAuthnException('Malformed RSA COSE key');
            }
            return [Der::rsaPublicKeyPem($n, $e), self::COSE_ALG_RS256];
        }

        throw new WebAuthnException('Unsupported COSE key type: ' . var_export($kty, true));
    }
}