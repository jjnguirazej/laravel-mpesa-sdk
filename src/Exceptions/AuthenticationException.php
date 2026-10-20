<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Exceptions;

/**
 * A API recusou as credenciais (por exemplo INS-2, chave de API inválida).
 *
 * Normalmente indica MPESA_API_KEY ou MPESA_PUBLIC_KEY incorretas, ou
 * credenciais de sandbox usadas em produção (ou vice-versa).
 */
class AuthenticationException extends ApiException
{
}
