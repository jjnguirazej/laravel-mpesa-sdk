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

    /**
     * Campos do payload que contêm credenciais e não devem ser gravados por ferramentas de monitorização.
     */
    public const SENSITIVE_PAYLOAD_FIELDS = [
        'input_SecurityCredential',
        'input_InitiatorIdentifier',
    ];

    public function boot(): void
    {
        $this->hideCredentialsFromTelescope();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mpesa.php' => $this->app->configPath('mpesa.php'),
            ], 'mpesa-config');
        }
    }

    /**
     * O Telescope grava os pedidos HTTP de saída e só oculta o cabeçalho Authorization
     * por omissão. Sem isto, as credenciais da reversão ficariam visíveis no painel.
     */
    private function hideCredentialsFromTelescope(): void
    {
        $telescope = 'Laravel\\Telescope\\Telescope';

        if (class_exists($telescope)) {
            $telescope::hideRequestParameters(self::SENSITIVE_PAYLOAD_FIELDS);
        }
    }
}
