<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Unit;

use Jjnguirazej\MpesaSdk\Responses\ResponseCode;
use Jjnguirazej\MpesaSdk\Responses\TransactionStatus;
use PHPUnit\Framework\TestCase;

final class ResponseCodeTest extends TestCase
{
    public function test_describes_documented_codes(): void
    {
        $this->assertSame('Request processed successfully', ResponseCode::describe(ResponseCode::SUCCESS));
        $this->assertSame('Insufficient balance', ResponseCode::describe('INS-2006'));
        $this->assertTrue(ResponseCode::isKnown('INS-17'));
    }

    public function test_unknown_code_has_no_description(): void
    {
        $this->assertNull(ResponseCode::describe('INS-9999'));
        $this->assertFalse(ResponseCode::isKnown('INS-9999'));
    }

    public function test_every_constant_has_description(): void
    {
        $constants = (new \ReflectionClass(ResponseCode::class))->getConstants(\ReflectionClassConstant::IS_PUBLIC);

        $this->assertCount(33, $constants);

        foreach ($constants as $name => $code) {
            $this->assertNotNull(ResponseCode::describe($code), "Sem descrição para {$name}.");
        }
    }

    public function test_transaction_status_parses_documented_values_only(): void
    {
        $this->assertSame(TransactionStatus::Completed, TransactionStatus::fromProvider('completed'));
        $this->assertSame(TransactionStatus::NotAvailable, TransactionStatus::fromProvider('N/A'));
        $this->assertNull(TransactionStatus::fromProvider('Success'));
        $this->assertNull(TransactionStatus::fromProvider(null));
    }
}
