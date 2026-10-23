<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Feature;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Support\Facades\Http;
use Jjnguirazej\MpesaSdk\Exceptions\ApiException;
use Jjnguirazej\MpesaSdk\Exceptions\AuthenticationException;
use Jjnguirazej\MpesaSdk\Exceptions\ConnectionException;
use Jjnguirazej\MpesaSdk\Exceptions\InvalidResponseException;
use Jjnguirazej\MpesaSdk\Exceptions\MpesaException;
use Jjnguirazej\MpesaSdk\MpesaClient;
use Jjnguirazej\MpesaSdk\Operation;
use Jjnguirazej\MpesaSdk\Tests\Fixtures\Responses;
use Jjnguirazej\MpesaSdk\Tests\TestCase;
use Throwable;

final class ErrorHandlingTest extends TestCase
{
    private function c2b(): void
    {
        $this->app->make(MpesaClient::class)->c2b(10, '841234567', 'Pedido 42', 'REF123');
    }

    private function catch(callable $callback): Throwable
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            return $exception;
        }

        $this->fail('Era esperada uma exceção.');
    }

    public function test_provider_rejection_throws_api_exception(): void
    {
        Http::fake(['*' => Http::response(Responses::error('INS-6', 'Transaction Failed'), 401)]);

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertNotInstanceOf(AuthenticationException::class, $exception);
        $this->assertSame('INS-6', $exception->responseCode);
        $this->assertSame('Transaction Failed', $exception->responseDescription);
        $this->assertSame(401, $exception->httpStatus);
        $this->assertSame(Operation::C2B, $exception->operation);
        $this->assertSame('INS-6', $exception->response['output_ResponseCode']);
    }

    public function test_cancelled_by_customer_is_identified(): void
    {
        Http::fake(['*' => Http::response(Responses::error('INS-5', 'Transaction cancelled by customer'), 401)]);

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertTrue($exception->isCancelledByCustomer());
    }

    public function test_duplicate_third_party_reference_is_identified(): void
    {
        Http::fake(['*' => Http::response(Responses::error('INS-10', 'Duplicate Transaction'), 409)]);

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertTrue($exception->isDuplicate());
    }

    public function test_provider_timeout_code_is_identified(): void
    {
        Http::fake(['*' => Http::response(Responses::error('INS-9', 'Request timeout'), 408)]);

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertTrue($exception->isRequestTimeout());
    }

    public function test_http_success_with_error_code_is_not_success(): void
    {
        Http::fake(['*' => Http::response(Responses::error('INS-2006', 'Insufficient balance'), 200)]);

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertTrue($exception->isInsufficientBalance());
    }

    public function test_success_code_with_error_status_is_not_success(): void
    {
        Http::fake(['*' => Http::response(Responses::success(), 500)]);

        $this->assertInstanceOf(ApiException::class, $this->catch(fn () => $this->c2b()));
    }

    public function test_invalid_api_key_throws_authentication_exception(): void
    {
        Http::fake(['*' => Http::response(Responses::error('INS-2', 'Invalid API Key'), 401)]);

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertSame('INS-2', $exception->responseCode);
    }

    public function test_unauthorized_without_body_throws_authentication_exception(): void
    {
        Http::fake(['*' => Http::response('', 401)]);

        $this->assertInstanceOf(AuthenticationException::class, $this->catch(fn () => $this->c2b()));
    }

    public function test_malformed_json_throws_invalid_response_exception(): void
    {
        Http::fake(['*' => Http::response('{"output_ResponseCode": "INS-0"', 200)]);

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(InvalidResponseException::class, $exception);
        $this->assertSame(200, $exception->httpStatus);
    }

    public function test_json_without_response_code_is_invalid(): void
    {
        Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

        $this->assertInstanceOf(InvalidResponseException::class, $this->catch(fn () => $this->c2b()));
    }

    public function test_unexpected_html_error_page_is_invalid(): void
    {
        Http::fake(['*' => Http::response('<html>Service Unavailable</html>', 503)]);

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(InvalidResponseException::class, $exception);
        $this->assertSame(503, $exception->httpStatus);
        $this->assertStringNotContainsString('Service Unavailable', $exception->getMessage());
    }

    public function test_connection_timeout_throws_connection_exception_and_is_not_retried(): void
    {
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectException(
                'cURL error 28: Operation timed out after 120000 milliseconds',
                new PsrRequest('POST', 'https://api.sandbox.vm.co.mz:18352/'),
            );
        });

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(ConnectionException::class, $exception);
        $this->assertTrue($exception->isTimeout());
        $this->assertStringContainsString('query()', $exception->getMessage());
        $this->assertSame(1, $attempts);
    }

    public function test_connection_refused_is_not_a_timeout(): void
    {
        Http::fake(fn () => throw new ConnectException(
            'cURL error 7: Failed to connect',
            new PsrRequest('POST', 'https://api.sandbox.vm.co.mz:18352/'),
        ));

        $exception = $this->catch(fn () => $this->c2b());

        $this->assertInstanceOf(ConnectionException::class, $exception);
        $this->assertFalse($exception->isTimeout());
    }

    public function test_failed_payment_is_not_retried(): void
    {
        Http::fake(['*' => Http::response(Responses::error('INS-1', 'Internal Error'), 500)]);

        $this->catch(fn () => $this->c2b());

        Http::assertSentCount(1);
    }

    public function test_all_exceptions_share_base_class(): void
    {
        Http::fake(['*' => Http::response('', 502)]);

        $this->assertInstanceOf(MpesaException::class, $this->catch(fn () => $this->c2b()));
    }

    public function test_exceptions_never_expose_credentials(): void
    {
        $scenarios = [
            fn () => Http::fake(['*' => Http::response(Responses::error('INS-2', 'Invalid API Key'), 401)]),
            fn () => Http::fake(['*' => Http::response(Responses::error('INS-6', 'Transaction Failed'), 401)]),
            fn () => Http::fake(['*' => Http::response('not json', 500)]),
            fn () => Http::fake(fn () => throw new ConnectException('cURL error 28: timed out', new PsrRequest('PUT', 'https://x'))),
        ];

        foreach ($scenarios as $scenario) {
            $this->app->forgetInstance(\Illuminate\Http\Client\Factory::class);
            Http::clearResolvedInstances();
            $this->app->forgetInstance(MpesaClient::class);
            $scenario();

            $exception = $this->catch(
                fn () => $this->app->make(MpesaClient::class)->reversal(10, 'abc123xyz', 'REF1'),
            );

            $output = implode("\n", [
                $exception->getMessage(),
                $exception->getTraceAsString(),
                json_encode(get_object_vars($exception)),
                (string) $exception->getPrevious()?->getMessage(),
            ]);

            $this->assertStringNotContainsString(self::API_KEY, $output);
            $this->assertStringNotContainsString(self::SECURITY_CREDENTIAL, $output);
            $this->assertStringNotContainsString(self::INITIATOR_IDENTIFIER, $output);
            $this->assertStringNotContainsString('Bearer', $exception->getMessage());
        }
    }
}
