<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk;

use Jjnguirazej\MpesaSdk\Exceptions\ApiException;
use Jjnguirazej\MpesaSdk\Exceptions\ConfigurationException;
use Jjnguirazej\MpesaSdk\Exceptions\ConnectionException;
use Jjnguirazej\MpesaSdk\Exceptions\InvalidResponseException;
use Jjnguirazej\MpesaSdk\Exceptions\ValidationException;
use Jjnguirazej\MpesaSdk\Http\Transport;
use Jjnguirazej\MpesaSdk\Responses\TransactionResponse;
use Jjnguirazej\MpesaSdk\Support\Msisdn;
use Jjnguirazej\MpesaSdk\Support\PayloadValidator;

/**
 * Ponto de entrada do SDK. Injete esta classe nos seus controllers ou serviços.
 *
 * Todos os métodos devolvem TransactionResponse apenas em caso de sucesso
 * (INS-0) e lançam uma subclasse de MpesaException em qualquer outro caso.
 */
final class MpesaClient
{
    public function __construct(
        private readonly Transport $transport,
        private readonly MpesaConfig $config,
    ) {
    }

    /**
     * Cobrança ao cliente (C2B). O cliente recebe um pedido de confirmação (PIN)
     * no telemóvel e a API só responde depois da confirmação, recusa ou expiração.
     *
     * @param  int|float|string  $amount  valor em meticais, até 2 casas decimais
     * @param  string  $msisdn  número do cliente (84/85), com ou sem 258
     * @param  string  $reference  referência da transação (input_TransactionReference)
     * @param  string  $thirdPartyReference  referência única gerada pela sua aplicação
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws ConnectionException
     * @throws InvalidResponseException
     */
    public function c2b(
        int|float|string $amount,
        string $msisdn,
        string $reference,
        string $thirdPartyReference,
    ): TransactionResponse {
        return $this->transport->send(Operation::C2B, [
            'input_ServiceProviderCode' => $this->config->serviceProviderCode,
            'input_CustomerMSISDN' => Msisdn::normalize($msisdn),
            'input_Amount' => PayloadValidator::amount($amount),
            'input_TransactionReference' => PayloadValidator::text($reference, 'reference'),
            'input_ThirdPartyReference' => PayloadValidator::text($thirdPartyReference, 'thirdPartyReference'),
        ]);
    }

    /**
     * Transferência da empresa para um cliente (B2C).
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws ConnectionException
     * @throws InvalidResponseException
     */
    public function b2c(
        int|float|string $amount,
        string $msisdn,
        string $reference,
        string $thirdPartyReference,
    ): TransactionResponse {
        return $this->transport->send(Operation::B2C, [
            'input_ServiceProviderCode' => $this->config->serviceProviderCode,
            'input_CustomerMSISDN' => Msisdn::normalize($msisdn),
            'input_Amount' => PayloadValidator::amount($amount),
            'input_TransactionReference' => PayloadValidator::text($reference, 'reference'),
            'input_ThirdPartyReference' => PayloadValidator::text($thirdPartyReference, 'thirdPartyReference'),
        ]);
    }

    /**
     * Transferência da empresa para outra entidade (B2B).
     *
     * @param  string  $receiverPartyCode  shortcode da entidade que recebe
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws ConnectionException
     * @throws InvalidResponseException
     */
    public function b2b(
        int|float|string $amount,
        string $receiverPartyCode,
        string $reference,
        string $thirdPartyReference,
    ): TransactionResponse {
        return $this->transport->send(Operation::B2B, [
            'input_Amount' => PayloadValidator::amount($amount),
            'input_TransactionReference' => PayloadValidator::text($reference, 'reference'),
            'input_ThirdPartyReference' => PayloadValidator::text($thirdPartyReference, 'thirdPartyReference'),
            'input_PrimaryPartyCode' => $this->config->serviceProviderCode,
            'input_ReceiverPartyCode' => PayloadValidator::text($receiverPartyCode, 'receiverPartyCode'),
        ]);
    }

    /**
     * Reversão (estorno) de uma transação. Exige MPESA_INITIATOR_IDENTIFIER e
     * MPESA_SECURITY_CREDENTIAL.
     *
     * @param  int|float|string  $amount  valor a reverter
     * @param  string  $transactionId  output_TransactionID da transação original
     *
     * @throws ConfigurationException
     * @throws ValidationException
     * @throws ApiException
     * @throws ConnectionException
     * @throws InvalidResponseException
     */
    public function reversal(
        int|float|string $amount,
        string $transactionId,
        string $thirdPartyReference,
    ): TransactionResponse {
        $initiator = $this->config->initiatorIdentifier
            ?? throw ConfigurationException::missing('initiator_identifier', 'MPESA_INITIATOR_IDENTIFIER');
        $credential = $this->config->securityCredential
            ?? throw ConfigurationException::missing('security_credential', 'MPESA_SECURITY_CREDENTIAL');

        return $this->transport->send(Operation::Reversal, [
            'input_Amount' => PayloadValidator::amount($amount),
            'input_TransactionID' => PayloadValidator::text($transactionId, 'transactionId'),
            'input_ThirdPartyReference' => PayloadValidator::text($thirdPartyReference, 'thirdPartyReference'),
            'input_ServiceProviderCode' => $this->config->serviceProviderCode,
            'input_InitiatorIdentifier' => $initiator,
            'input_SecurityCredential' => $credential,
        ]);
    }

    /**
     * Consulta o estado de uma transação.
     *
     * @param  string  $queryReference  output_TransactionID ou output_ConversationID da transação
     * @param  string  $thirdPartyReference  referência única desta consulta
     *
     * @throws ValidationException
     * @throws ApiException
     * @throws ConnectionException
     * @throws InvalidResponseException
     */
    public function query(string $queryReference, string $thirdPartyReference): TransactionResponse
    {
        return $this->transport->send(Operation::Query, [
            'input_QueryReference' => PayloadValidator::text($queryReference, 'queryReference'),
            'input_ServiceProviderCode' => $this->config->serviceProviderCode,
            'input_ThirdPartyReference' => PayloadValidator::text($thirdPartyReference, 'thirdPartyReference'),
        ]);
    }
}
