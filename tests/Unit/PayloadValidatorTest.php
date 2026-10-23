<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Unit;

use Jjnguirazej\MpesaSdk\Exceptions\ValidationException;
use Jjnguirazej\MpesaSdk\Support\PayloadValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PayloadValidatorTest extends TestCase
{
    /**
     * @return array<string, array{int|float|string, string}>
     */
    public static function validAmounts(): array
    {
        return [
            'inteiro' => [10, '10'],
            'texto inteiro' => ['10', '10'],
            'decimal' => [10.5, '10.5'],
            'duas casas' => ['10.25', '10.25'],
            'zeros à direita' => ['10.00', '10'],
            'cêntimos' => ['0.01', '0.01'],
        ];
    }

    #[DataProvider('validAmounts')]
    public function test_normalizes_valid_amounts(int|float|string $input, string $expected): void
    {
        $this->assertSame($expected, PayloadValidator::amount($input));
    }

    /**
     * @return array<string, array{int|float|string}>
     */
    public static function invalidAmounts(): array
    {
        return [
            'zero' => [0],
            'zero decimal' => ['0.00'],
            'negativo' => [-5],
            'três casas decimais' => ['10.123'],
            'texto' => ['dez'],
            'vazio' => [''],
            'notação científica' => ['1e3'],
            'vírgula' => ['10,50'],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_rejects_invalid_amounts(int|float|string $input): void
    {
        $this->expectException(ValidationException::class);

        PayloadValidator::amount($input);
    }

    public function test_rejects_empty_text(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('reference');

        PayloadValidator::text('   ', 'reference');
    }

    public function test_rejects_control_characters(): void
    {
        $this->expectException(ValidationException::class);

        PayloadValidator::text("REF\n123", 'reference');
    }

    public function test_trims_text(): void
    {
        $this->assertSame('REF123', PayloadValidator::text('  REF123 ', 'reference'));
    }
}
