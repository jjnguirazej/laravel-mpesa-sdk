<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Feature;

use Illuminate\Support\ServiceProvider;
use Jjnguirazej\MpesaSdk\Exceptions\ConfigurationException;
use Jjnguirazej\MpesaSdk\Facades\Mpesa;
use Jjnguirazej\MpesaSdk\MpesaClient;
use Jjnguirazej\MpesaSdk\MpesaConfig;
use Jjnguirazej\MpesaSdk\MpesaSdkServiceProvider;
use Jjnguirazej\MpesaSdk\Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_client_is_resolved_as_singleton(): void
    {
        $client = $this->app->make(MpesaClient::class);

        $this->assertInstanceOf(MpesaClient::class, $client);
        $this->assertSame($client, $this->app->make(MpesaClient::class));
    }

    public function test_facade_resolves_client(): void
    {
        $this->assertSame($this->app->make(MpesaClient::class), Mpesa::getFacadeRoot());
    }

    public function test_package_config_is_merged(): void
    {
        $this->assertSame(self::SERVICE_PROVIDER_CODE, config('mpesa.service_provider_code'));
        $this->assertSame('sandbox', config('mpesa.environment'));
    }

    public function test_config_file_defaults_to_sandbox(): void
    {
        $config = require __DIR__.'/../../config/mpesa.php';

        $this->assertSame('sandbox', $config['environment']);
        $this->assertSame(120, $config['timeout']);
    }

    public function test_config_is_publishable_with_tag(): void
    {
        $paths = ServiceProvider::pathsToPublish(MpesaSdkServiceProvider::class, 'mpesa-config');

        $this->assertCount(1, $paths);
        $this->assertSame(config_path('mpesa.php'), array_values($paths)[0]);
        $this->assertFileExists(array_key_first($paths));
    }

    public function test_vendor_publish_copies_config(): void
    {
        $target = config_path('mpesa.php');
        @unlink($target);

        $this->artisan('vendor:publish', ['--tag' => 'mpesa-config'])->assertSuccessful();

        $this->assertFileExists($target);
        unlink($target);
    }

    public function test_composer_declares_package_discovery(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true);

        $this->assertSame('jjnguirazej/mpesa-sdk', $composer['name']);
        $this->assertContains(MpesaSdkServiceProvider::class, $composer['extra']['laravel']['providers']);
        $this->assertSame(Mpesa::class, $composer['extra']['laravel']['aliases']['Mpesa']);
    }

    public function test_missing_configuration_fails_on_first_use(): void
    {
        config()->set('mpesa.api_key', null);
        $this->app->forgetInstance(MpesaConfig::class);
        $this->app->forgetInstance(MpesaClient::class);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('MPESA_API_KEY');

        $this->app->make(MpesaClient::class);
    }
}
