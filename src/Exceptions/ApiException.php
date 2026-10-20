<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Exceptions;

use Jjnguirazej\MpesaSdk\Operation;
use Jjnguirazej\MpesaSdk\Responses\ResponseCode;

/**
 * A API respondeu, mas recusou ou não concluiu a operação (output_ResponseCode diferente de INS-0).
 */
class ApiException extends MpesaException
{
    /**
     * @param  array<string, mixed>  $response  corpo JSON devolvido pela API
     */
    public function __construct(
        string $message,
        public readonly Operation $operation,
        public readonly int $httpStatus,
        public readonly string $responseCode,
        public readonly string $responseDescription,
        public readonly array $response = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromResponse(Operation $operation, int $httpStatus, array $response): static
    {
        $code = (string) ($response['output_ResponseCode'] ?? '');
        $description = (string) ($response['output_ResponseDesc'] ?? '');

        return new static(
            "A API M-Pesa recusou a operação {$operation->value}: [{$code}] {$description} (HTTP {$httpStatus}).",
            $operation,
            $httpStatus,
            $code,
            $description,
            $response,
        );
    }

    /**
     * O mesmo input_ThirdPartyReference já foi utilizado (INS-10).
     */
    public function isDuplicate(): bool
    {
        return $this->responseCode === ResponseCode::DUPLICATE_TRANSACTION;
    }

    /**
     * O cliente cancelou a operação no telemóvel (INS-5).
     */
    public function isCancelledByCustomer(): bool
    {
        return $this->responseCode === ResponseCode::CANCELLED_BY_CUSTOMER;
    }

    /**
     * A API não obteve confirmação do cliente a tempo (INS-9).
     */
    public function isRequestTimeout(): bool
    {
        return $this->responseCode === ResponseCode::REQUEST_TIMEOUT;
    }

    /**
     * Saldo insuficiente (INS-2006).
     */
    public function isInsufficientBalance(): bool
    {
        return $this->responseCode === ResponseCode::INSUFFICIENT_BALANCE;
    }
}
