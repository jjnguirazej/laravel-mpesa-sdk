<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Unit;

use Jjnguirazej\MpesaSdk\Exceptions\ValidationException;
use Jjnguirazej\MpesaSdk\Support\Msisdn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MsisdnTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function validNumbers(): array
    {
        return [
            'local 84' => ['841234567', '258841234567'],
            'local 85' => ['851234567', '258851234567'],
            'com 258' => ['258841234567', '258841234567'],
            'com +258' => ['+258851234567', '258851234567'],
            'com 00258' => ['00258841234567', '258841234567'],
            'com espaços' => [' 84 123 4567 ', '258841234567'],
            'com hífens' => ['84-123-4567', '258841234567'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_normalizes_valid_vodacom_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, Msisdn::normalize($input));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'outra operadora' => ['821234567'],
            'curto' => ['84123456'],
            'longo' => ['8412345678'],
            'letras' => ['84abc4567'],
            'vazio' => [''],
            'outro país' => ['+27841234567'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_rejects_invalid_numbers(string $input): void
    {
        $this->expectException(ValidationException::class);

        Msisdn::normalize($input);
    }

    public function test_exception_reports_field_without_echoing_number(): void
    {
        try {
            Msisdn::normalize('821234567', 'phone');
            $this->fail('Era esperada uma ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame('phone', $exception->field);
            $this->assertStringNotContainsString('821234567', $exception->getMessage());
        }
    }
}
