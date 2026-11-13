<?php

declare(strict_types=1);

namespace WV;

/**
 * Veldversleuteling met libsodium.
 *
 *  - seal()/open(): XChaCha20-Poly1305 (AEAD). Het adres van de rij ("tabel:id")
 *    wordt als geauthenticeerde context meegegeven, zodat een versleutelde waarde
 *    niet naar een andere rij of tabel verplaatst kan worden.
 *  - index(): HMAC-SHA256 ("blind index") om op e-mail en tokens te kunnen
 *    zoeken zonder de waarde zelf te bewaren.
 *  - Uit de ene hoofdsleutel (APP_KEY) worden aparte subsleutels afgeleid voor
 *    versleuteling, indexen en handtekeningen.
 */
final class Crypto
{
    private const KDF_CONTEXT = 'WVERKLAR';
    private const PAD_BLOCK = 256;

    private string $encKey;
    private string $indexKey;
    private string $macKey;

    public function __construct(string $masterKey)
    {
        if (strlen($masterKey) !== SODIUM_CRYPTO_KDF_KEYBYTES) {
            throw new \RuntimeException('APP_KEY moet exact 32 bytes zijn.');
        }
        $this->encKey = sodium_crypto_kdf_derive_from_key(32, 1, self::KDF_CONTEXT, $masterKey);
        $this->indexKey = sodium_crypto_kdf_derive_from_key(32, 2, self::KDF_CONTEXT, $masterKey);
        $this->macKey = sodium_crypto_kdf_derive_from_key(32, 3, self::KDF_CONTEXT, $masterKey);
    }

    /** Leest "base64:..." (of kale base64) en geeft de 32 ruwe bytes terug. */
    public static function parseKey(string $value): string
    {
        $value = trim($value);
        if (str_starts_with($value, 'base64:')) {
            $value = substr($value, 7);
        }
        $raw = base64_decode($value, true);
        if ($raw === false || strlen($raw) !== 32) {
            throw new \RuntimeException('APP_KEY is ongeldig. Genereer een nieuwe met: php bin/console key:generate');
        }
        return $raw;
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    public function seal(string $plain, string $aad): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $padded = sodium_pad($plain, self::PAD_BLOCK);
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($padded, $aad, $nonce, $this->encKey);
        return 'v1.' . rtrim(strtr(base64_encode($nonce . $cipher), '+/', '-_'), '=');
    }

    public function open(string $sealed, string $aad): string
    {
        if (!str_starts_with($sealed, 'v1.')) {
            throw new DecryptException('Onbekend versleutelformaat.');
        }
        $raw = base64_decode(strtr(substr($sealed, 3), '-_', '+/'), true);
        $nonceLen = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if ($raw === false || strlen($raw) < $nonceLen + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES) {
            throw new DecryptException('Beschadigde versleutelde waarde.');
        }
        $padded = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($raw, $nonceLen),
            $aad,
            substr($raw, 0, $nonceLen),
            $this->encKey
        );
        if ($padded === false) {
            throw new DecryptException('Ontsleutelen mislukt (verkeerde sleutel of gemanipuleerde gegevens).');
        }
        try {
            return sodium_unpad($padded, self::PAD_BLOCK);
        } catch (\SodiumException) {
            throw new DecryptException('Beschadigde opvulling.');
        }
    }

    public function sealJson(array $data, string $aad): string
    {
        return $this->seal(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $aad);
    }

    public function openJson(string $sealed, string $aad): array
    {
        $data = json_decode($this->open($sealed, $aad), true);
        if (!is_array($data)) {
            throw new DecryptException('Ongeldige gegevens na ontsleutelen.');
        }
        return $data;
    }

    /** Deterministische, niet-omkeerbare zoekwaarde (hex). */
    public function index(string $purpose, string $value): string
    {
        return hash_hmac('sha256', $purpose . "\0" . $value, $this->indexKey);
    }

    /** Handtekening voor bijv. bevestigingslinks (hex). */
    public function mac(string $purpose, string $value): string
    {
        return hash_hmac('sha256', $purpose . "\0" . $value, $this->macKey);
    }
}
