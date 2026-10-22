<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Regista a configuração e os serviços do SDK no container do Laravel.
 *
 * A configuração só é validada quando o MpesaClient é usado pela primeira vez,
 * para que a aplicação arranque normalmente mesmo sem credenciais M-Pesa.
 */
class MpesaSdkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mpesa.php', 'mpesa');

        $this->app->singleton(
            MpesaConfig::class,
            fn (Application $app): MpesaConfig => MpesaConfig::fromArray((array) $app['config']->get('mpesa', [])),
        );

        $this->app->singleton(MpesaClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mpesa.php' => $this->app->configPath('mpesa.php'),
            ], 'mpesa-config');
        }
    }
}
