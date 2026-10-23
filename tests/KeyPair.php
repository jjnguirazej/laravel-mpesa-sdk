<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests;

use OpenSSLAsymmetricKey;

/**
 * Par de chaves RSA gerado em memória para os testes (nunca uma chave real).
 */
final class KeyPair
{
    private static ?OpenSSLAsymmetricKey $privateKey = null;

    public static function privateKey(): OpenSSLAsymmetricKey
    {
        return self::$privateKey ??= openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
    }

    public static function publicKeyPem(): string
    {
        return openssl_pkey_get_details(self::privateKey())['key'];
    }

    /**
     * Apenas o conteúdo base64, como é apresentado no portal da Vodacom.
     */
    public static function publicKeyBody(): string
    {
        return preg_replace('/-----[A-Z ]+-----|\s+/', '', self::publicKeyPem());
    }

    public static function decrypt(string $token): string
    {
        openssl_private_decrypt(base64_decode($token), $plain, self::privateKey(), OPENSSL_PKCS1_PADDING);

        return $plain;
    }
}
