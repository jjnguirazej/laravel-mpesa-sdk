<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk;

use Jjnguirazej\MpesaSdk\Exceptions\ConfigurationException;
use SensitiveParameter;

/**
 * Configuração validada do SDK.
 *
 * Os segredos nunca aparecem em var_dump(), dd() ou print_r() graças a __debugInfo().
 */
final class MpesaConfig
{
    private const HOSTNAME_PATTERN = '/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i';

    public function __construct(
        public readonly Environment $environment,
        public readonly string $apiHost,
        #[SensitiveParameter] public readonly string $apiKey,
        public readonly string $publicKey,
        public readonly string $serviceProviderCode,
        public readonly string $origin = '*',
        #[SensitiveParameter] public readonly ?string $initiatorIdentifier = null,
        #[SensitiveParameter] public readonly ?string $securityCredential = null,
        public readonly int $timeout = 120,
        public readonly int $connectTimeout = 10,
    ) {
    }

    /**
     * Cria a configuração a partir do array de config/mpesa.php, validando cada valor.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws ConfigurationException
     */
    public static function fromArray(#[SensitiveParameter] array $config): self
    {
        $environment = Environment::tryFrom(strtolower(trim((string) ($config['environment'] ?? ''))));

        if ($environment === null) {
            throw ConfigurationException::invalid('environment', 'use "sandbox" ou "production" (MPESA_ENV).');
        }

        $apiHost = self::optionalString($config, 'api_host') ?? $environment->host();

        if (preg_match(self::HOSTNAME_PATTERN, $apiHost) !== 1) {
            throw ConfigurationException::invalid('api_host', 'indique apenas o nome do host, sem protocolo, porta ou caminho (MPESA_API_HOST).');
        }

        return new self(
            environment: $environment,
            apiHost: strtolower($apiHost),
            apiKey: self::requiredString($config, 'api_key', 'MPESA_API_KEY'),
            publicKey: self::requiredString($config, 'public_key', 'MPESA_PUBLIC_KEY'),
            serviceProviderCode: self::requiredString($config, 'service_provider_code', 'MPESA_SERVICE_PROVIDER_CODE'),
            origin: self::optionalString($config, 'origin') ?? '*',
            initiatorIdentifier: self::optionalString($config, 'initiator_identifier'),
            securityCredential: self::optionalString($config, 'security_credential'),
            timeout: self::positiveInt($config, 'timeout', 120),
            connectTimeout: self::positiveInt($config, 'connect_timeout', 10),
        );
    }

    /**
     * URL base (sempre HTTPS) para a operação indicada.
     */
    public function baseUrl(Operation $operation): string
    {
        return "https://{$this->apiHost}:{$operation->port()}";
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'environment' => $this->environment,
            'apiHost' => $this->apiHost,
            'apiKey' => '***',
            'publicKey' => '***',
            'serviceProviderCode' => $this->serviceProviderCode,
            'origin' => $this->origin,
            'initiatorIdentifier' => $this->initiatorIdentifier === null ? null : '***',
            'securityCredential' => $this->securityCredential === null ? null : '***',
            'timeout' => $this->timeout,
            'connectTimeout' => $this->connectTimeout,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function requiredString(array $config, string $key, string $envVariable): string
    {
        return self::optionalString($config, $key) ?? throw ConfigurationException::missing($key, $envVariable);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function optionalString(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_int($value)) {
            throw ConfigurationException::invalid($key, 'deve ser um texto.');
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function positiveInt(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? $default;

        if (! is_numeric($value) || (int) $value <= 0) {
            throw ConfigurationException::invalid($key, 'deve ser um número inteiro maior que zero.');
        }

        return (int) $value;
    }
}
