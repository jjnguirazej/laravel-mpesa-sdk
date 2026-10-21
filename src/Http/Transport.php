<?php

declare(strict_types=1);

namespace Jjnguirazej\MpesaSdk\Http;

use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Jjnguirazej\MpesaSdk\Auth\BearerTokenGenerator;
use Jjnguirazej\MpesaSdk\Exceptions\ApiException;
use Jjnguirazej\MpesaSdk\Exceptions\AuthenticationException;
use Jjnguirazej\MpesaSdk\Exceptions\ConnectionException;
use Jjnguirazej\MpesaSdk\Exceptions\InvalidResponseException;
use Jjnguirazej\MpesaSdk\MpesaConfig;
use Jjnguirazej\MpesaSdk\Operation;
use Jjnguirazej\MpesaSdk\Responses\ResponseCode;
use Jjnguirazej\MpesaSdk\Responses\TransactionResponse;

/**
 * Único ponto do SDK que comunica com a API.
 *
 * Responsável pelos cabeçalhos, autenticação, tempos limite e pela
 * interpretação da resposta. Não repete pedidos automaticamente: repetir um
 * pagamento sem saber se o primeiro foi processado pode cobrar duas vezes.
 */
final class Transport
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly MpesaConfig $config,
        private readonly BearerTokenGenerator $tokens,
    ) {
    }

    /**
     * @param  array<string, string>  $payload
     *
     * @throws ApiException
     * @throws ConnectionException
     * @throws InvalidResponseException
     */
    public function send(Operation $operation, array $payload): TransactionResponse
    {
        $request = $this->http
            ->baseUrl($this->config->baseUrl($operation))
            ->acceptJson()
            ->withToken($this->tokens->generate())
            ->withHeaders(['Origin' => $this->config->origin])
            ->timeout($this->config->timeout)
            ->connectTimeout($this->config->connectTimeout)
            ->withOptions([
                'verify' => true,
                'allow_redirects' => false,
            ]);

        $options = $operation->method() === 'GET'
            ? ['query' => $payload]
            : ['json' => $payload];

        try {
            $response = $request->send($operation->method(), $operation->path(), $options);
        } catch (HttpConnectionException $exception) {
            throw ConnectionException::fromTransport($operation, $exception);
        }

        return $this->interpret($operation, $response);
    }

    /**
     * Converte a resposta HTTP em TransactionResponse ou na exceção adequada.
     */
    private function interpret(Operation $operation, Response $response): TransactionResponse
    {
        $status = $response->status();
        $body = json_decode($response->body(), true);

        if (! is_array($body) || ! isset($body['output_ResponseCode']) || ! is_string($body['output_ResponseCode'])) {
            if ($status === 401 || $status === 403) {
                throw new AuthenticationException(
                    "A API M-Pesa recusou as credenciais na operação {$operation->value} (HTTP {$status}).",
                    $operation,
                    $status,
                    '',
                    '',
                );
            }

            throw InvalidResponseException::malformed($operation, $status);
        }

        $code = $body['output_ResponseCode'];

        if ($response->successful() && $code === ResponseCode::SUCCESS) {
            return TransactionResponse::fromArray($operation, $status, $body);
        }

        if ($code === ResponseCode::INVALID_API_KEY) {
            throw AuthenticationException::fromResponse($operation, $status, $body);
        }

        throw ApiException::fromResponse($operation, $status, $body);
    }
}
