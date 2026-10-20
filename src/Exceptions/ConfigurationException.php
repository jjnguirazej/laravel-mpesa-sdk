<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Exceptions;

/**
 * Configuração em falta ou inválida (ficheiro config/mpesa.php ou variáveis .env).
 */
class ConfigurationException extends MpesaException
{
    public static function missing(string $key, string $envVariable): self
    {
        return new self("A configuração mpesa.{$key} é obrigatória. Defina {$envVariable} no ficheiro .env.");
    }

    public static function invalid(string $key, string $reason): self
    {
        return new self("A configuração mpesa.{$key} é inválida: {$reason}");
    }

    public static function invalidPublicKey(): self
    {
        return new self('Não foi possível ler a chave pública M-Pesa (mpesa.public_key). Confirme o valor de MPESA_PUBLIC_KEY.');
    }

    public static function tokenGenerationFailed(): self
    {
        return new self('Não foi possível gerar o token de autenticação com a chave pública e a chave de API configuradas.');
    }
}
