<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Jjnguirazej\MpesaSdk\Exceptions\ConfigurationException;
use Jjnguirazej\MpesaSdk\Exceptions\ValidationException;
use Jjnguirazej\MpesaSdk\MpesaClient;
use Jjnguirazej\MpesaSdk\MpesaConfig;
use Jjnguirazej\MpesaSdk\Operation;
use Jjnguirazej\MpesaSdk\Responses\TransactionStatus;
use Jjnguirazej\MpesaSdk\Tests\Fixtures\Responses;
use Jjnguirazej\MpesaSdk\Tests\KeyPair;
use Jjnguirazej\MpesaSdk\Tests\TestCase;

final class OperationsTest extends TestCase
{
    private function client(): MpesaClient
    {
        return $this->app->make(MpesaClient::class);
    }

    private function assertSignedWithApiKey(Request $request): void
    {
        $header = $request->header('Authorization')[0] ?? '';

        $this->assertStringStartsWith('Bearer ', $header);
        $this->assertSame(self::API_KEY, KeyPair::decrypt(substr($header, 7)));
        $this->assertSame('*', $request->header('Origin')[0] ?? null);
    }

    public function test_c2b_sends_documented_payload_and_returns_response(): void
    {
        Http::fake(['*' => Http::response(Responses::success(), 201)]);

        $response = $this->client()->c2b('10.50', '841234567', 'Pedido 42', 'REF123');

        Http::assertSent(function (Request $request): bool {
            $this->assertSame('POST', $request->method());
            $this->assertSame('https://api.sandbox.vm.co.mz:18352/ipg/v1x/c2bPayment/singleStage/', $request->url());
            $this->assertSame([
                'input_ServiceProviderCode' => self::SERVICE_PROVIDER_CODE,
                'input_CustomerMSISDN' => '258841234567',
                'input_Amount' => '10.5',
                'input_TransactionReference' => 'Pedido 42',
                'input_ThirdPartyReference' => 'REF123',
            ], $request->data());
            $this->assertSignedWithApiKey($request);

            return true;
        });

        $this->assertSame(Operation::C2B, $response->operation);
        $this->assertSame(201, $response->httpStatus);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('abc123xyz', $response->transactionId);
        $this->assertSame('d8f0a5b8a1c24c0a9b7f3e2d1c0b9a87', $response->conversationId);
        $this->assertSame('REF123', $response->thirdPartyReference);
        $this->assertSame(Responses::success(), $response->raw);
        $this->assertSame('INS-0', $response->toArray()['response_code']);
    }

    public function test_b2c_uses_b2c_endpoint(): void
    {
        Http::fake(['*' => Http::response(Responses::success(), 200)]);

        $this->client()->b2c(100, '+258851234567', 'Comissao', 'REF124');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.sandbox.vm.co.mz:18345/ipg/v1x/b2cPayment/'
            && $request['input_CustomerMSISDN'] === '258851234567'
            && $request['input_Amount'] === '100');
    }

    public function test_b2b_sends_primary_and_receiver_party_codes(): void
    {
        Http::fake(['*' => Http::response(Responses::success(), 200)]);

        $this->client()->b2b(50, '979797', 'Fornecedor', 'REF125');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.sandbox.vm.co.mz:18349/ipg/v1x/b2bPayment/'
            && $request->data() === [
                'input_Amount' => '50',
                'input_TransactionReference' => 'Fornecedor',
                'input_ThirdPartyReference' => 'REF125',
                'input_PrimaryPartyCode' => self::SERVICE_PROVIDER_CODE,
                'input_ReceiverPartyCode' => '979797',
            ]);
    }

    public function test_reversal_sends_documented_payload_with_put(): void
    {
        Http::fake(['*' => Http::response(Responses::success(), 200)]);

        $this->client()->reversal(10, 'abc123xyz', 'REF126');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://api.sandbox.vm.co.mz:18354/ipg/v1x/reversal/'
            && $request->data() === [
                'input_TransactionID' => 'abc123xyz',
                'input_SecurityCredential' => self::SECURITY_CREDENTIAL,
                'input_InitiatorIdentifier' => self::INITIATOR_IDENTIFIER,
                'input_ThirdPartyReference' => 'REF126',
                'input_ServiceProviderCode' => self::SERVICE_PROVIDER_CODE,
                'input_ReversalAmount' => '10',
            ]);
    }

    public function test_full_reversal_omits_reversal_amount(): void
    {
        Http::fake(['*' => Http::response(Responses::success(), 200)]);

        $this->client()->reversal(null, 'abc123xyz', 'REF127');

        Http::assertSent(fn (Request $request): bool => ! array_key_exists('input_ReversalAmount', $request->data())
            && ! array_key_exists('input_Amount', $request->data())
            && $request['input_TransactionID'] === 'abc123xyz');
    }

    public function test_reversal_requires_initiator_configuration(): void
    {
        Http::fake();
        config()->set('mpesa.security_credential', null);
        $this->app->forgetInstance(MpesaConfig::class);
        $this->app->forgetInstance(MpesaClient::class);

        try {
            $this->client()->reversal(10, 'abc123xyz', 'REF126');
            $this->fail('Era esperada uma ConfigurationException.');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString('MPESA_SECURITY_CREDENTIAL', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_query_uses_get_with_query_string(): void
    {
        Http::fake(['*' => Http::response([
            'output_ConversationID' => 'd8f0a5b8a1c24c0a9b7f3e2d1c0b9a87',
            'output_ResponseCode' => 'INS-0',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ResponseTransactionStatus' => 'Completed',
            'output_ThirdPartyReference' => 'Q1',
        ], 200)]);

        $response = $this->client()->query('abc123xyz', 'Q1');

        Http::assertSent(function (Request $request): bool {
            $this->assertSame('GET', $request->method());
            $this->assertStringStartsWith('https://api.sandbox.vm.co.mz:18353/ipg/v1x/queryTransactionStatus/?', $request->url());
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $this->assertSame([
                'input_QueryReference' => 'abc123xyz',
                'input_ServiceProviderCode' => self::SERVICE_PROVIDER_CODE,
                'input_ThirdPartyReference' => 'Q1',
            ], $query);
            $this->assertSignedWithApiKey($request);

            return true;
        });

        $this->assertSame('Completed', $response->transactionStatus);
        $this->assertTrue($response->isCompleted());
    }

    /**
     * @return array<string, array{string, ?TransactionStatus}>
     */
    public static function nonCompletedStatuses(): array
    {
        return [
            'cancelada' => ['Cancelled', TransactionStatus::Cancelled],
            'expirada' => ['Expired', TransactionStatus::Expired],
            'n/a' => ['N/A', TransactionStatus::NotAvailable],
            'não documentado' => ['Pending', null],
        ];
    }

    #[DataProvider('nonCompletedStatuses')]
    public function test_query_with_other_status_is_not_completed(string $raw, ?TransactionStatus $expected): void
    {
        Http::fake(['*' => Http::response(Responses::success(['output_ResponseTransactionStatus' => $raw]), 200)]);

        $response = $this->client()->query('abc123xyz', 'Q2');

        $this->assertFalse($response->isCompleted());
        $this->assertSame($expected, $response->status());
        $this->assertSame($raw, $response->transactionStatus);
    }

    public function test_reference_with_twenty_characters_is_accepted(): void
    {
        Http::fake(['*' => Http::response(Responses::success(), 201)]);

        $this->client()->c2b(10, '841234567', str_repeat('A', 20), 'B');

        Http::assertSentCount(1);
    }

    public function test_synchronous_response_is_not_awaiting_result(): void
    {
        Http::fake(['*' => Http::response(Responses::success(), 201)]);

        $this->assertFalse($this->client()->c2b(10, '841234567', 'A', 'B')->isAwaitingResult());
    }

    public function test_asynchronous_acceptance_is_awaiting_result(): void
    {
        $async = Responses::success();
        unset($async['output_TransactionID']);
        Http::fake(['*' => Http::response($async, 201)]);

        $response = $this->client()->b2c(10, '841234567', 'A', 'B');

        $this->assertTrue($response->isAwaitingResult());
        $this->assertSame('d8f0a5b8a1c24c0a9b7f3e2d1c0b9a87', $response->conversationId);
        $this->assertTrue($response->toArray()['awaiting_result']);
    }

    public function test_payment_response_without_status_is_not_completed(): void
    {
        Http::fake(['*' => Http::response(Responses::success(), 201)]);

        $this->assertFalse($this->client()->c2b(10, '841234567', 'A', 'B')->isCompleted());
    }

    public function test_production_environment_uses_production_host(): void
    {
        config()->set('mpesa.environment', 'production');
        $this->app->forgetInstance(MpesaConfig::class);
        $this->app->forgetInstance(MpesaClient::class);
        Http::fake(['*' => Http::response(Responses::success(), 201)]);

        $this->client()->c2b(10, '841234567', 'A', 'B');

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.vm.co.mz:18352/'));
    }

    public function test_invalid_input_never_reaches_the_api(): void
    {
        Http::fake();

        foreach ([
            fn () => $this->client()->c2b(0, '841234567', 'A', 'B'),
            fn () => $this->client()->c2b(10, '821234567', 'A', 'B'),
            fn () => $this->client()->c2b(10, '841234567', '', 'B'),
            fn () => $this->client()->b2b(10, ' ', 'A', 'B'),
            fn () => $this->client()->query('', 'B'),
            fn () => $this->client()->c2b(10, '841234567', str_repeat('A', 21), 'B'),
            fn () => $this->client()->reversal(0, 'abc123xyz', 'B'),
        ] as $call) {
            try {
                $call();
                $this->fail('Era esperada uma ValidationException.');
            } catch (ValidationException) {
            }
        }

        Http::assertNothingSent();
    }
}
