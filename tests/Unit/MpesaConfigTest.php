<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Unit;

use Jjnguirazej\MpesaSdk\Environment;
use Jjnguirazej\MpesaSdk\Exceptions\ConfigurationException;
use Jjnguirazej\MpesaSdk\MpesaConfig;
use Jjnguirazej\MpesaSdk\Operation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MpesaConfigTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function validConfig(array $overrides = []): array
    {
        return array_merge([
            'environment' => 'sandbox',
            'api_key' => 'secret-api-key',
            'public_key' => 'public-key-body',
            'service_provider_code' => '171717',
            'initiator_identifier' => 'secret-initiator',
            'security_credential' => 'secret-credential',
        ], $overrides);
    }

    public function test_uses_sandbox_host_by_environment(): void
    {
        $config = MpesaConfig::fromArray($this->validConfig());

        $this->assertSame(Environment::Sandbox, $config->environment);
        $this->assertSame('https://api.sandbox.vm.co.mz:18352', $config->baseUrl(Operation::C2B));
    }

    public function test_uses_production_host_only_when_explicit(): void
    {
        $config = MpesaConfig::fromArray($this->validConfig(['environment' => 'production']));

        $this->assertSame('api.vm.co.mz', $config->apiHost);
    }

    public function test_applies_defaults(): void
    {
        $config = MpesaConfig::fromArray($this->validConfig());

        $this->assertSame('*', $config->origin);
        $this->assertSame(120, $config->timeout);
        $this->assertSame(10, $config->connectTimeout);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredKeys(): array
    {
        return [
            'api_key' => ['api_key'],
            'public_key' => ['public_key'],
            'service_provider_code' => ['service_provider_code'],
        ];
    }

    #[DataProvider('requiredKeys')]
    public function test_fails_when_required_value_is_missing(string $key): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("mpesa.{$key}");

        MpesaConfig::fromArray($this->validConfig([$key => '']));
    }

    public function test_fails_on_unknown_environment(): void
    {
        $this->expectException(ConfigurationException::class);

        MpesaConfig::fromArray($this->validConfig(['environment' => 'live']));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeHosts(): array
    {
        return [
            'com protocolo' => ['https://api.vm.co.mz'],
            'com porta' => ['api.vm.co.mz:18352'],
            'com caminho' => ['api.vm.co.mz/ipg'],
            'com credenciais' => ['user@evil.example'],
            'sem domínio' => ['localhost'],
        ];
    }

    #[DataProvider('unsafeHosts')]
    public function test_rejects_unsafe_api_host(string $host): void
    {
        $this->expectException(ConfigurationException::class);

        MpesaConfig::fromArray($this->validConfig(['api_host' => $host]));
    }

    public function test_accepts_custom_hostname(): void
    {
        $config = MpesaConfig::fromArray($this->validConfig(['api_host' => 'API.Example.co.mz']));

        $this->assertSame('https://api.example.co.mz:18353', $config->baseUrl(Operation::Query));
    }

    public function test_rejects_non_positive_timeout(): void
    {
        $this->expectException(ConfigurationException::class);

        MpesaConfig::fromArray($this->validConfig(['timeout' => 0]));
    }

    public function test_debug_output_hides_secrets(): void
    {
        $config = MpesaConfig::fromArray($this->validConfig());

        $dump = print_r($config, true);

        $this->assertStringNotContainsString('secret-api-key', $dump);
        $this->assertStringNotContainsString('secret-initiator', $dump);
        $this->assertStringNotContainsString('secret-credential', $dump);
        $this->assertStringContainsString('171717', $dump);
    }
}
