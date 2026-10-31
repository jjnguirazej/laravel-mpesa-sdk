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
 *
 * A exceção original não é encadeada (getPrevious() é null): ela contém o pedido
 * HTTP completo, incluindo o cabeçalho Authorization e as credenciais do payload.
 * Apenas o código de erro cURL é preservado.
 */
class ConnectionException extends MpesaException
{
    public function __construct(
        string $message,
        public readonly Operation $operation,
        public readonly bool $timedOut,
        public readonly ?int $curlError = null,
    ) {
        parent::__construct($message);
    }

    public static function fromTransport(Operation $operation, Throwable $transportError): self
    {
        $curlError = preg_match('/cURL error (\d+)/', $transportError->getMessage(), $matches) === 1
            ? (int) $matches[1]
            : null;

        $timedOut = $curlError === 28 || str_contains(strtolower($transportError->getMessage()), 'timed out');

        $suffix = $curlError === null ? '' : " (cURL {$curlError})";

        $message = $timedOut
            ? "Tempo limite excedido na operação {$operation->value}{$suffix}. O estado da transação é desconhecido; confirme com query() antes de repetir."
            : "Falha de ligação à API M-Pesa na operação {$operation->value}{$suffix}.";

        return new self($message, $operation, $timedOut, $curlError);
    }

    public function isTimeout(): bool
    {
        return $this->timedOut;
    }
}
