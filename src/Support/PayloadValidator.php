<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Support;

use Jjnguirazej\MpesaSdk\Exceptions\ValidationException;

/**
 * Validações aplicadas antes de enviar qualquer pedido à API.
 */
final class PayloadValidator
{
    /**
     * Valida o valor e devolve-o como texto com no máximo 2 casas decimais (ex.: "10" ou "10.50").
     *
     * Valores com mais de 2 casas decimais são rejeitados em vez de arredondados,
     * para que o montante cobrado seja sempre exatamente o pedido.
     *
     * @throws ValidationException
     */
    public static function amount(int|float|string $amount, string $field = 'amount'): string
    {
        $value = is_string($amount) ? trim($amount) : (string) $amount;

        if (preg_match('/^\d+(?:\.\d{1,2})?$/', $value) !== 1) {
            throw new ValidationException(
                'O valor deve ser um número positivo com no máximo 2 casas decimais.',
                $field,
            );
        }

        if ((float) $value <= 0) {
            throw new ValidationException('O valor deve ser maior que zero.', $field);
        }

        $normalized = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return $normalized;
    }

    /**
     * Garante um identificador ou referência não vazio, sem caracteres de controlo
     * e, se indicado, com tamanho máximo.
     *
     * @throws ValidationException
     */
    public static function text(string $value, string $field, ?int $maxLength = null): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new ValidationException("O campo {$field} é obrigatório.", $field);
        }

        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            throw new ValidationException("O campo {$field} deve ter no máximo {$maxLength} caracteres.", $field);
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new ValidationException("O campo {$field} contém caracteres inválidos.", $field);
        }

        return $value;
    }
}
