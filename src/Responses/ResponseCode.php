<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Responses;

/**
 * Códigos output_ResponseCode da API M-Pesa (tabela "Response Codes & Descriptions" do portal).
 *
 * Apenas INS-0 representa sucesso.
 */
final class ResponseCode
{
    public const SUCCESS = 'INS-0';

    public const INTERNAL_ERROR = 'INS-1';

    public const INVALID_API_KEY = 'INS-2';

    public const USER_NOT_ACTIVE = 'INS-4';

    public const CANCELLED_BY_CUSTOMER = 'INS-5';

    public const TRANSACTION_FAILED = 'INS-6';

    public const REQUEST_TIMEOUT = 'INS-9';

    public const DUPLICATE_TRANSACTION = 'INS-10';

    public const INVALID_SHORTCODE = 'INS-13';

    public const INVALID_REFERENCE = 'INS-14';

    public const INVALID_AMOUNT = 'INS-15';

    public const TEMPORARY_OVERLOAD = 'INS-16';

    public const INVALID_TRANSACTION_REFERENCE_LENGTH = 'INS-17';

    public const INVALID_TRANSACTION_ID = 'INS-18';

    public const INVALID_THIRD_PARTY_REFERENCE = 'INS-19';

    public const MISSING_PARAMETERS = 'INS-20';

    public const PARAMETER_VALIDATION_FAILED = 'INS-21';

    public const INVALID_OPERATION_TYPE = 'INS-22';

    public const UNKNOWN_STATUS = 'INS-23';

    public const INVALID_INITIATOR_IDENTIFIER = 'INS-24';

    public const INVALID_SECURITY_CREDENTIAL = 'INS-25';

    public const NOT_AUTHORIZED = 'INS-26';

    public const DIRECT_DEBIT_MISSING = 'INS-993';

    public const DIRECT_DEBIT_ALREADY_EXISTS = 'INS-994';

    public const CUSTOMER_PROFILE_PROBLEM = 'INS-995';

    public const CUSTOMER_ACCOUNT_NOT_ACTIVE = 'INS-996';

    public const LINKING_TRANSACTION_NOT_FOUND = 'INS-997';

    public const INVALID_MARKET = 'INS-998';

    public const INITIATOR_AUTHENTICATION_ERROR = 'INS-2001';

    public const INVALID_RECEIVER = 'INS-2002';

    public const INSUFFICIENT_BALANCE = 'INS-2006';

    public const INVALID_MSISDN = 'INS-2051';

    public const INVALID_LANGUAGE_CODE = 'INS-2057';

    /**
     * Descrições oficiais (em inglês, como na documentação da Vodacom).
     */
    private const DESCRIPTIONS = [
        self::SUCCESS => 'Request processed successfully',
        self::INTERNAL_ERROR => 'Internal Error',
        self::INVALID_API_KEY => 'Invalid API Key',
        self::USER_NOT_ACTIVE => 'User is not active',
        self::CANCELLED_BY_CUSTOMER => 'Transaction cancelled by customer',
        self::TRANSACTION_FAILED => 'Transaction Failed',
        self::REQUEST_TIMEOUT => 'Request timeout',
        self::DUPLICATE_TRANSACTION => 'Duplicate Transaction',
        self::INVALID_SHORTCODE => 'Invalid Shortcode Used',
        self::INVALID_REFERENCE => 'Invalid Reference Used',
        self::INVALID_AMOUNT => 'Invalid Amount Used',
        self::TEMPORARY_OVERLOAD => 'Unable to handle the request due to a temporary overloading',
        self::INVALID_TRANSACTION_REFERENCE_LENGTH => 'Invalid Transaction Reference. Length Should Be Between 1 and 20.',
        self::INVALID_TRANSACTION_ID => 'Invalid TransactionID Used',
        self::INVALID_THIRD_PARTY_REFERENCE => 'Invalid ThirdPartyReference Used',
        self::MISSING_PARAMETERS => 'Not All Parameters Provided. Please try again.',
        self::PARAMETER_VALIDATION_FAILED => 'Parameter validations failed. Please try again.',
        self::INVALID_OPERATION_TYPE => 'Invalid Operation Type',
        self::UNKNOWN_STATUS => 'Unknown Status. Contact M-Pesa Support',
        self::INVALID_INITIATOR_IDENTIFIER => 'Invalid InitiatorIdentifier Used',
        self::INVALID_SECURITY_CREDENTIAL => 'Invalid SecurityCredential Used',
        self::NOT_AUTHORIZED => 'Not authorized',
        self::DIRECT_DEBIT_MISSING => 'Direct Debit Missing',
        self::DIRECT_DEBIT_ALREADY_EXISTS => 'Direct Debit Already Exists',
        self::CUSTOMER_PROFILE_PROBLEM => "Customer's Profile Has Problems",
        self::CUSTOMER_ACCOUNT_NOT_ACTIVE => 'Customer Account Status Not Active',
        self::LINKING_TRANSACTION_NOT_FOUND => 'Linking Transaction Not Found',
        self::INVALID_MARKET => 'Invalid Market',
        self::INITIATOR_AUTHENTICATION_ERROR => 'Initiator authentication error.',
        self::INVALID_RECEIVER => 'Receiver invalid.',
        self::INSUFFICIENT_BALANCE => 'Insufficient balance',
        self::INVALID_MSISDN => 'MSISDN invalid.',
        self::INVALID_LANGUAGE_CODE => 'Language code invalid.',
    ];

    /**
     * Descrição oficial do código, ou null se o código não constar da documentação.
     */
    public static function describe(string $code): ?string
    {
        return self::DESCRIPTIONS[$code] ?? null;
    }

    /**
     * O código consta da tabela oficial.
     */
    public static function isKnown(string $code): bool
    {
        return isset(self::DESCRIPTIONS[$code]);
    }
}
