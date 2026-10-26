<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Responses;

/**
 * Valores documentados de output_ResponseTransactionStatus / input_ResponseTransactionStatus.
 */
enum TransactionStatus: string
{
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
    case Expired = 'Expired';
    case NotAvailable = 'N/A';

    /**
     * Converte o texto da API; devolve null para valores não documentados.
     */
    public static function fromProvider(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, trim($value)) === 0) {
                return $case;
            }
        }

        return null;
    }
}
