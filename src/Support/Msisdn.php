<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Support;

use Jjnguirazej\MpesaSdk\Exceptions\ValidationException;

/**
 * Validação e normalização de números Vodacom Moçambique (prefixos 84 e 85).
 *
 * Baseado no ValidationHelper de Kishan Nareshpal Jadav (abdulmueid/mpesa-php-api, MIT).
 */
final class Msisdn
{
    /**
     * Normaliza para o formato 258(84|85)XXXXXXX aceite pela API.
     *
     * Formatos aceites: 84XXXXXXX, 25884XXXXXXX, +25884XXXXXXX e 0025884XXXXXXX.
     * Espaços e hífens são ignorados.
     *
     * @throws ValidationException
     */
    public static function normalize(string $msisdn, string $field = 'msisdn'): string
    {
        $clean = str_replace([' ', '-'], '', trim($msisdn));

        if (preg_match('/^(?:\+|00)?(?:258)?((?:84|85)\d{7})$/', $clean, $matches) !== 1) {
            throw new ValidationException(
                'O número de telefone indicado não é um número M-Pesa Vodacom válido (prefixos 84 ou 85).',
                $field,
            );
        }

        return '258'.$matches[1];
    }
}
