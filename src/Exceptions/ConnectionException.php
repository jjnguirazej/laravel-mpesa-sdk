<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Exceptions;

use Jjnguirazej\MpesaSdk\Operation;
use Throwable;

/**
 * Falha de rede: ligação recusada, erro de TLS ou tempo limite excedido.
 *
 * Atenção: em operações financeiras, o estado da transação é desconhecido.
 * A API pode ter processado o pedido. Confirme com query() antes de repetir.
 */
class ConnectionException extends MpesaException
{
    public function __construct(
        string $message,
        public readonly Operation $operation,
        public readonly bool $timedOut,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromTransport(Operation $operation, Throwable $previous): self
    {
        $timedOut = str_contains(strtolower($previous->getMessage()), 'timed out')
            || str_contains($previous->getMessage(), 'cURL error 28');

        $message = $timedOut
            ? "Tempo limite excedido na operação {$operation->value}. O estado da transação é desconhecido; confirme com query() antes de repetir."
            : "Falha de ligação à API M-Pesa na operação {$operation->value}.";

        return new self($message, $operation, $timedOut, $previous);
    }

    public function isTimeout(): bool
    {
        return $this->timedOut;
    }
}
