<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Unit;

use Jjnguirazej\MpesaSdk\Auth\BearerTokenGenerator;
use Jjnguirazej\MpesaSdk\Environment;
use Jjnguirazej\MpesaSdk\Exceptions\ConfigurationException;
use Jjnguirazej\MpesaSdk\MpesaConfig;
use Jjnguirazej\MpesaSdk\Tests\KeyPair;
use PHPUnit\Framework\TestCase;

final class BearerTokenGeneratorTest extends TestCase
{
    private function config(string $publicKey, string $apiKey = 'my-api-key'): MpesaConfig
    {
        return new MpesaConfig(
            environment: Environment::Sandbox,
            apiHost: Environment::Sandbox->host(),
            apiKey: $apiKey,
            publicKey: $publicKey,
            serviceProviderCode: '171717',
        );
    }

    public function test_token_is_api_key_encrypted_with_public_key(): void
    {
        $token = (new BearerTokenGenerator($this->config(KeyPair::publicKeyBody())))->generate();

        $this->assertSame('my-api-key', KeyPair::decrypt($token));
    }

    public function test_accepts_full_pem_public_key(): void
    {
        $token = (new BearerTokenGenerator($this->config(KeyPair::publicKeyPem())))->generate();

        $this->assertSame('my-api-key', KeyPair::decrypt($token));
    }

    public function test_invalid_public_key_throws_configuration_exception_without_leaking(): void
    {
        try {
            (new BearerTokenGenerator($this->config('not-a-valid-key', 'super-secret')))->generate();
            $this->fail('Era esperada uma ConfigurationException.');
        } catch (ConfigurationException $exception) {
            $this->assertStringNotContainsString('super-secret', $exception->getMessage());
            $this->assertStringNotContainsString('not-a-valid-key', $exception->getMessage());
        }

        $this->assertFalse(openssl_error_string(), 'A fila de erros do OpenSSL deve ficar vazia.');
    }
}
