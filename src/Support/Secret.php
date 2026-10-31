<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Support;

use JsonSerializable;
use LogicException;
use SensitiveParameter;
use WeakMap;

/**
 * Guarda um segredo (chave de API, credencial) fora das propriedades do objeto.
 *
 * O valor fica num WeakMap estático, por isso não aparece em dd(), var_dump(),
 * var_export(), print_r(), json_encode() nem em casts para array. A serialização
 * é proibida para impedir que o segredo acabe em filas, cache ou sessões.
 */
final class Secret implements JsonSerializable
{
    /**
     * @var WeakMap<self, string>|null
     */
    private static ?WeakMap $values = null;

    public function __construct(#[SensitiveParameter] string $value)
    {
        self::$values ??= new WeakMap();
        self::$values[$this] = $value;
    }

    /**
     * Devolve o valor real. Use apenas no momento de o enviar à API.
     */
    public function reveal(): string
    {
        return self::$values[$this];
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => '***'];
    }

    public function jsonSerialize(): string
    {
        return '***';
    }

    public function __toString(): string
    {
        return '***';
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        throw new LogicException('As credenciais M-Pesa não podem ser serializadas. Resolva o MpesaClient no método handle() do job, não no construtor.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        throw new LogicException('As credenciais M-Pesa não podem ser desserializadas.');
    }

    private function __clone()
    {
    }
}
