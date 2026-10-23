<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Fixtures;

/**
 * Respostas fictícias no formato da API M-Pesa (dados inventados para testes).
 */
final class Responses
{
    /**
     * @return array<string, string>
     */
    public static function success(array $overrides = []): array
    {
        return array_merge([
            'output_ConversationID' => 'd8f0a5b8a1c24c0a9b7f3e2d1c0b9a87',
            'output_ResponseCode' => 'INS-0',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ThirdPartyReference' => 'REF123',
            'output_TransactionID' => 'abc123xyz',
        ], $overrides);
    }

    /**
     * @return array<string, string>
     */
    public static function error(string $code, string $description): array
    {
        return [
            'output_ConversationID' => 'e1b2c3d4e5f60718293a4b5c6d7e8f90',
            'output_ResponseCode' => $code,
            'output_ResponseDesc' => $description,
            'output_ThirdPartyReference' => 'REF123',
            'output_TransactionID' => 'N/A',
        ];
    }
}
