<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests;

use Jjnguirazej\MpesaSdk\MpesaSdkServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public const API_KEY = 'fake-api-key-for-tests';

    public const SERVICE_PROVIDER_CODE = '171717';

    public const INITIATOR_IDENTIFIER = 'fake-initiator';

    public const SECURITY_CREDENTIAL = 'fake-security-credential';

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [MpesaSdkServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('mpesa', $this->mpesaConfig());
    }

    /**
     * @return array<string, mixed>
     */
    protected function mpesaConfig(): array
    {
        return [
            'environment' => 'sandbox',
            'api_host' => null,
            'api_key' => self::API_KEY,
            'public_key' => KeyPair::publicKeyBody(),
            'service_provider_code' => self::SERVICE_PROVIDER_CODE,
            'origin' => '*',
            'initiator_identifier' => self::INITIATOR_IDENTIFIER,
            'security_credential' => self::SECURITY_CREDENTIAL,
            'timeout' => 120,
            'connect_timeout' => 10,
        ];
    }
}
