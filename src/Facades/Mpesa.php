<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Facades;

use Illuminate\Support\Facades\Facade;
use Jjnguirazej\MpesaSdk\MpesaClient;
use Jjnguirazej\MpesaSdk\Responses\TransactionResponse;

/**
 * Facade opcional para o MpesaClient.
 *
 * @method static TransactionResponse c2b(int|float|string $amount, string $msisdn, string $reference, string $thirdPartyReference)
 * @method static TransactionResponse b2c(int|float|string $amount, string $msisdn, string $reference, string $thirdPartyReference)
 * @method static TransactionResponse b2b(int|float|string $amount, string $receiverPartyCode, string $reference, string $thirdPartyReference)
 * @method static TransactionResponse reversal(int|float|string $amount, string $transactionId, string $thirdPartyReference)
 * @method static TransactionResponse query(string $queryReference, string $thirdPartyReference)
 *
 * @see MpesaClient
 */
class Mpesa extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MpesaClient::class;
    }
}
