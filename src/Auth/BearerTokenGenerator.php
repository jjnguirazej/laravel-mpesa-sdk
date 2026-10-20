<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Auth;

use Jjnguirazej\MpesaSdk\Exceptions\ConfigurationException;
use Jjnguirazej\MpesaSdk\MpesaConfig;

/**
 * Gera o token Bearer exigido pela API M-Pesa Moçambique.
 *
 * O token é a chave de API cifrada com a chave pública RSA da Vodacom
 * (padding PKCS#1 v1.5) e codificada em base64. A geração é local e não
 * envolve nenhum pedido de rede, por isso não existe cache nem renovação.
 */
final class BearerTokenGenerator
{
    public function __construct(private readonly MpesaConfig $config)
    {
    }

    /**
     * @throws ConfigurationException
     */
    public function generate(): string
    {
        $key = openssl_pkey_get_public($this->pem());

        if ($key === false) {
            $this->clearOpenSslErrors();

            throw ConfigurationException::invalidPublicKey();
        }

        $encrypted = '';

        if (! openssl_public_encrypt($this->config->apiKey, $encrypted, $key, OPENSSL_PKCS1_PADDING)) {
            $this->clearOpenSslErrors();

            throw ConfigurationException::tokenGenerationFailed();
        }

        return base64_encode($encrypted);
    }

    /**
     * Aceita a chave em formato PEM ou apenas o conteúdo base64 (como no portal).
     */
    private function pem(): string
    {
        $key = trim($this->config->publicKey);

        if (str_contains($key, '-----BEGIN')) {
            return $key;
        }

        $body = preg_replace('/\s+/', '', $key) ?? '';

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split($body, 64, "\n")
            .'-----END PUBLIC KEY-----';
    }

    /**
     * Esvazia a fila de erros do OpenSSL para não contaminar operações seguintes.
     */
    private function clearOpenSslErrors(): void
    {
        while (openssl_error_string() !== false) {
        }
    }
}
