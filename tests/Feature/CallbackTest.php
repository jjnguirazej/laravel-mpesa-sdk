<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Jjnguirazej\MpesaSdk\Callbacks\CallbackResult;
use Jjnguirazej\MpesaSdk\Exceptions\InvalidCallbackException;
use Jjnguirazej\MpesaSdk\Exceptions\MpesaException;
use Jjnguirazej\MpesaSdk\Facades\Mpesa;
use Jjnguirazej\MpesaSdk\MpesaClient;
use Jjnguirazej\MpesaSdk\Responses\TransactionStatus;
use Jjnguirazej\MpesaSdk\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CallbackTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'input_OriginalConversationID' => '241fasif1f1n92fn129nfasnf91nf1',
            'input_ThirdPartyReference' => '11114',
            'input_ResultCode' => '0',
            'input_ResultDesc' => 'Request Processed Successfully',
            'input_ResponseTransactionStatus' => 'Completed',
        ], $overrides);
    }

    public function test_parses_documented_payment_result(): void
    {
        $result = $this->app->make(MpesaClient::class)->parseCallback($this->payload());

        $this->assertSame('241fasif1f1n92fn129nfasnf91nf1', $result->originalConversationId);
        $this->assertSame('11114', $result->thirdPartyReference);
        $this->assertSame('0', $result->resultCode);
        $this->assertSame(TransactionStatus::Completed, $result->status());
        $this->assertTrue($result->isSuccessful());
        $this->assertNull($result->transactionId);
    }

    public function test_parses_reversal_result_with_transaction_id(): void
    {
        $payload = $this->payload(['input_TransactionID' => '4XDF12345']);
        unset($payload['input_ResponseTransactionStatus']);

        $result = CallbackResult::fromArray($payload);

        $this->assertSame('4XDF12345', $result->transactionId);
        $this->assertNull($result->transactionStatus);
        $this->assertTrue($result->isSuccessful());
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function unsuccessfulResults(): array
    {
        return [
            'código diferente de 0' => [['input_ResultCode' => 'INS-6']],
            'cancelada' => [['input_ResponseTransactionStatus' => 'Cancelled']],
            'expirada' => [['input_ResponseTransactionStatus' => 'Expired']],
            'n/a' => [['input_ResponseTransactionStatus' => 'N/A']],
            'estado não documentado' => [['input_ResponseTransactionStatus' => 'Success']],
        ];
    }

    #[DataProvider('unsuccessfulResults')]
    public function test_unsuccessful_results_are_not_successful(array $overrides): void
    {
        $this->assertFalse(CallbackResult::fromArray($this->payload($overrides))->isSuccessful());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredFields(): array
    {
        return [
            'conversation id' => ['input_OriginalConversationID'],
            'result code' => ['input_ResultCode'],
        ];
    }

    #[DataProvider('requiredFields')]
    public function test_rejects_payload_without_required_field(string $field): void
    {
        $payload = $this->payload();
        unset($payload[$field]);

        $this->expectException(InvalidCallbackException::class);
        $this->expectExceptionMessage($field);

        CallbackResult::fromArray($payload);
    }

    public function test_invalid_callback_extends_base_exception(): void
    {
        $this->expectException(MpesaException::class);

        CallbackResult::fromArray([]);
    }

    public function test_acknowledgement_matches_documented_format(): void
    {
        $this->assertSame([
            'output_OriginalConversationID' => '241fasif1f1n92fn129nfasnf91nf1',
            'output_ResponseDesc' => 'Successfully Accepted Result',
            'output_ResponseCode' => '0',
            'output_ThirdPartyConversationID' => '11114',
        ], CallbackResult::fromArray($this->payload())->acknowledgement());
    }

    public function test_listener_route_can_parse_and_acknowledge(): void
    {
        Route::post('/mpesa/callback', function (Request $request) {
            $result = Mpesa::parseCallback($request->json()->all());

            return response()->json($result->acknowledgement());
        });

        $this->postJson('/mpesa/callback', $this->payload())
            ->assertOk()
            ->assertExactJson([
                'output_OriginalConversationID' => '241fasif1f1n92fn129nfasnf91nf1',
                'output_ResponseDesc' => 'Successfully Accepted Result',
                'output_ResponseCode' => '0',
                'output_ThirdPartyConversationID' => '11114',
            ]);
    }
}
