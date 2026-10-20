<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Exceptions;

use Jjnguirazej\MpesaSdk\Operation;

/**
 * Resposta da API que não pode ser interpretada (JSON inválido ou sem output_ResponseCode).
 *
 * O corpo da resposta não é incluído na mensagem para evitar a exposição de dados.
 */
class InvalidResponseException extends MpesaException
{
    public function __construct(
        string $message,
        public readonly Operation $operation,
        public readonly int $httpStatus,
    ) {
        parent::__construct($message);
    }

    public static function malformed(Operation $operation, int $httpStatus): self
    {
        return new self(
            "Resposta inválida da API M-Pesa na operação {$operation->value} (HTTP {$httpStatus}).",
            $operation,
            $httpStatus,
        );
    }
}
