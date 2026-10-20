<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk;

/**
 * Ambientes da API M-Pesa e respetivos hosts.
 */
enum Environment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';

    public function host(): string
    {
        return match ($this) {
            self::Sandbox => 'api.sandbox.vm.co.mz',
            self::Production => 'api.vm.co.mz',
        };
    }
}
