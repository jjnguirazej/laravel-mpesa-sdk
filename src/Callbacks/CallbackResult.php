<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Callbacks;

use Jjnguirazej\MpesaSdk\Exceptions\InvalidCallbackException;
use Jjnguirazej\MpesaSdk\Responses\TransactionStatus;

/**
 * Resultado assíncrono enviado pela M-Pesa para o Response URL configurado no portal.
 *
 * A documentação não define assinatura nem outro mecanismo para provar que o
 * pedido veio da M-Pesa. Trate o conteúdo como não confiável e confirme o
 * estado com query() antes de entregar bens ou serviços.
 */
final class CallbackResult
{
    /**
     * Código de sucesso de input_ResultCode no resultado assíncrono.
     */
    public const SUCCESS = '0';

    /**
     * @param  array<string, mixed>  $raw  corpo original recebido
     */
    public function __construct(
        public readonly string $originalConversationId,
        public readonly ?string $thirdPartyReference,
        public readonly ?string $transactionId,
        public readonly string $resultCode,
        public readonly string $resultDescription,
        public readonly ?string $transactionStatus,
        public readonly array $raw,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload  corpo JSON do pedido (ex.: $request->json()->all())
     *
     * @throws InvalidCallbackException
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            originalConversationId: self::required($payload, 'input_OriginalConversationID'),
            thirdPartyReference: self::optional($payload, 'input_ThirdPartyReference'),
            transactionId: self::optional($payload, 'input_TransactionID'),
            resultCode: self::required($payload, 'input_ResultCode'),
            resultDescription: (string) self::optional($payload, 'input_ResultDesc'),
            transactionStatus: self::optional($payload, 'input_ResponseTransactionStatus'),
            raw: $payload,
        );
    }

    /**
     * A M-Pesa reporta a transação como processada (input_ResultCode = "0").
     *
     * Quando input_ResponseTransactionStatus está presente, exige também "Completed".
     */
    public function isSuccessful(): bool
    {
        if ($this->resultCode !== self::SUCCESS) {
            return false;
        }

        return $this->transactionStatus === null || $this->status() === TransactionStatus::Completed;
    }

    /**
     * Estado documentado da transação, ou null se ausente ou não documentado.
     */
    public function status(): ?TransactionStatus
    {
        return TransactionStatus::fromProvider($this->transactionStatus);
    }

    /**
     * Corpo de resposta que o listener deve devolver à M-Pesa a confirmar a receção.
     *
     * @return array<string, string>
     */
    public function acknowledgement(string $description = 'Successfully Accepted Result'): array
    {
        return [
            'output_OriginalConversationID' => $this->originalConversationId,
            'output_ResponseDesc' => $description,
            'output_ResponseCode' => self::SUCCESS,
            'output_ThirdPartyConversationID' => (string) $this->thirdPartyReference,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function required(array $payload, string $key): string
    {
        return self::optional($payload, $key) ?? throw InvalidCallbackException::missingField($key);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function optional(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }
}
