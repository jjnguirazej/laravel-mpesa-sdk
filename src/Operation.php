<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk;

/**
 * Operações suportadas e respetivos endpoints da API M-Pesa (Vodacom Moçambique).
 *
 * As portas e caminhos são fixos no código para que nenhum valor externo
 * consiga redirecionar os pedidos para outro destino.
 */
enum Operation: string
{
    case C2B = 'c2b';
    case B2C = 'b2c';
    case B2B = 'b2b';
    case Reversal = 'reversal';
    case Query = 'query';

    public function port(): int
    {
        return match ($this) {
            self::C2B => 18352,
            self::B2C => 18345,
            self::B2B => 18349,
            self::Reversal => 18354,
            self::Query => 18353,
        };
    }

    public function path(): string
    {
        return match ($this) {
            self::C2B => '/ipg/v1x/c2bPayment/singleStage/',
            self::B2C => '/ipg/v1x/b2cPayment/',
            self::B2B => '/ipg/v1x/b2bPayment/',
            self::Reversal => '/ipg/v1x/reversal/',
            self::Query => '/ipg/v1x/queryTransactionStatus/',
        };
    }

    public function method(): string
    {
        return match ($this) {
            self::Reversal => 'PUT',
            self::Query => 'GET',
            default => 'POST',
        };
    }
}
