<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Responses;

use JsonSerializable;
use Jjnguirazej\MpesaSdk\Operation;

/**
 * Resposta bem-sucedida (output_ResponseCode = INS-0) de uma operação M-Pesa.
 *
 * O SDK só devolve esta classe em caso de sucesso. Qualquer outro resultado
 * resulta numa exceção, por isso nunca é preciso verificar o código manualmente.
 *
 * Em modo assíncrono, sucesso significa apenas "pedido aceite": ver isAwaitingResult().
 */
final class TransactionResponse implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $raw  corpo JSON original devolvido pela API
     */
    public function __construct(
        public readonly Operation $operation,
        public readonly int $httpStatus,
        public readonly string $responseCode,
        public readonly string $responseDescription,
        public readonly ?string $transactionId,
        public readonly ?string $conversationId,
        public readonly ?string $thirdPartyReference,
        public readonly ?string $transactionStatus,
        public readonly array $raw,
    ) {
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromArray(Operation $operation, int $httpStatus, array $body): self
    {
        return new self(
            operation: $operation,
            httpStatus: $httpStatus,
            responseCode: (string) ($body['output_ResponseCode'] ?? ''),
            responseDescription: (string) ($body['output_ResponseDesc'] ?? ''),
            transactionId: self::stringOrNull($body, 'output_TransactionID'),
            conversationId: self::stringOrNull($body, 'output_ConversationID'),
            thirdPartyReference: self::stringOrNull($body, 'output_ThirdPartyReference'),
            transactionStatus: self::stringOrNull($body, 'output_ResponseTransactionStatus'),
            raw: $body,
        );
    }

    /**
     * A API aceitou e processou o pedido (INS-0).
     */
    public function isSuccessful(): bool
    {
        return $this->responseCode === ResponseCode::SUCCESS;
    }

    /**
     * Resposta imediata do modo assíncrono: o pedido foi aceite, mas o resultado
     * final só chega ao Response URL (ou por query()).
     *
     * Detetado pela ausência de output_TransactionID em operações financeiras,
     * que é o formato documentado da resposta assíncrona.
     */
    public function isAwaitingResult(): bool
    {
        return $this->operation !== Operation::Query && $this->transactionId === null;
    }

    /**
     * Estado documentado da transação (apenas em query()), ou null se ausente ou não documentado.
     */
    public function status(): ?TransactionStatus
    {
        return TransactionStatus::fromProvider($this->transactionStatus);
    }

    /**
     * Apenas para query(): a transação consultada tem o estado "Completed".
     *
     * Qualquer outro estado (ou estado ausente) devolve false; estados
     * desconhecidos nunca são tratados como pagamento concluído.
     */
    public function isCompleted(): bool
    {
        return $this->status() === TransactionStatus::Completed;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'operation' => $this->operation->value,
            'http_status' => $this->httpStatus,
            'response_code' => $this->responseCode,
            'response_description' => $this->responseDescription,
            'transaction_id' => $this->transactionId,
            'conversation_id' => $this->conversationId,
            'third_party_reference' => $this->thirdPartyReference,
            'transaction_status' => $this->transactionStatus,
            'awaiting_result' => $this->isAwaitingResult(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function stringOrNull(array $body, string $key): ?string
    {
        $value = $body[$key] ?? null;

        if (! is_scalar($value) || (string) $value === '') {
            return null;
        }

        return (string) $value;
    }
}
