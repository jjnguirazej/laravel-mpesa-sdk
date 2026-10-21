<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Responses;

/**
 * Códigos output_ResponseCode usados pelo SDK.
 *
 * Apenas INS-0 representa sucesso. A lista completa de códigos está no
 * portal de desenvolvedores da Vodacom; confirme lá o significado dos restantes.
 */
final class ResponseCode
{
    public const SUCCESS = 'INS-0';

    public const INVALID_API_KEY = 'INS-2';

    public const CANCELLED_BY_CUSTOMER = 'INS-5';

    public const TRANSACTION_FAILED = 'INS-6';

    public const REQUEST_TIMEOUT = 'INS-9';

    public const DUPLICATE_TRANSACTION = 'INS-10';

    public const INSUFFICIENT_BALANCE = 'INS-2006';
}
