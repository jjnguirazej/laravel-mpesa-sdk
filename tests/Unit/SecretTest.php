<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Tests\Unit;

use Jjnguirazej\MpesaSdk\Environment;
use Jjnguirazej\MpesaSdk\MpesaConfig;
use Jjnguirazej\MpesaSdk\Support\Secret;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

final class SecretTest extends TestCase
{
    private const SECRETS = ['secret-api-key', 'secret-initiator', 'secret-credential'];

    private function config(): MpesaConfig
    {
        return new MpesaConfig(
            environment: Environment::Sandbox,
            apiHost: Environment::Sandbox->host(),
            apiKey: 'secret-api-key',
            publicKey: 'public-key',
            serviceProviderCode: '171717',
            initiatorIdentifier: 'secret-initiator',
            securityCredential: 'secret-credential',
        );
    }

    private function assertHidden(string $output, string $label): void
    {
        foreach (self::SECRETS as $secret) {
            $this->assertStringNotContainsString($secret, $output, "Segredo exposto via {$label}.");
        }
    }

    public function test_reveal_returns_original_value(): void
    {
        $this->assertSame('abc', (new Secret('abc'))->reveal());
        $this->assertSame('secret-api-key', $this->config()->apiKey->reveal());
    }

    public function test_config_secrets_are_hidden_from_dumps(): void
    {
        $config = $this->config();

        $this->assertHidden(print_r($config, true), 'print_r');
        $this->assertHidden(var_export($config, true), 'var_export');
        $this->assertHidden(print_r((array) $config, true), 'cast para array');
        $this->assertHidden((string) json_encode($config), 'json_encode');

        ob_start();
        var_dump($config);
        $this->assertHidden((string) ob_get_clean(), 'var_dump');
    }

    public function test_config_secrets_are_hidden_from_dd(): void
    {
        $output = '';
        (new CliDumper())->dump(
            (new VarCloner())->cloneVar($this->config()),
            function (string $line) use (&$output): void {
                $output .= $line;
            },
        );

        $this->assertHidden($output, 'dd()/dump()');
        $this->assertStringContainsString('171717', $output);
    }

    public function test_config_cannot_be_serialized(): void
    {
        $this->expectException(LogicException::class);

        serialize($this->config());
    }

    public function test_secret_cannot_be_unserialized(): void
    {
        $this->expectException(LogicException::class);

        $class = Secret::class;

        unserialize(sprintf('O:%d:"%s":1:{s:5:"value";s:3:"abc";}', strlen($class), $class));
    }

    public function test_secret_cannot_be_cloned(): void
    {
        $this->expectException(\Error::class);

        $secret = new Secret('abc');
        clone $secret;
    }
}
