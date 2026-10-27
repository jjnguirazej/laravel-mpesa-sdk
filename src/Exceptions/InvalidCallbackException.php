<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Exceptions;

/**
 * Pedido recebido no Response URL que não tem o formato de resultado assíncrono da M-Pesa.
 */
class InvalidCallbackException extends MpesaException
{
    public static function missingField(string $field): self
    {
        return new self("O callback M-Pesa não contém o campo obrigatório {$field}.");
    }
}
