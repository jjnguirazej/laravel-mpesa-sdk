<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Exceptions;

/**
 * Parâmetros inválidos detetados antes de qualquer pedido à API.
 * Nenhum pedido é enviado quando esta exceção é lançada.
 */
class ValidationException extends MpesaException
{
    public function __construct(
        string $message,
        public readonly string $field,
    ) {
        parent::__construct($message);
    }
}
