# Tratamento de erros

## Hierarquia

```
RuntimeException
└── MpesaException
    ├── ConfigurationException
    ├── ValidationException            (field)
    ├── ConnectionException            (operation, isTimeout())
    ├── InvalidResponseException       (operation, httpStatus)
    ├── InvalidCallbackException       (callback sem campos obrigatórios)
    └── ApiException                   (operation, httpStatus, responseCode, responseDescription, response)
        └── AuthenticationException
```

Todas estão em `Jjnguirazej\MpesaSdk\Exceptions`.

## Como o SDK decide

1. Erro de rede → `ConnectionException`.
2. Corpo sem JSON válido ou sem `output_ResponseCode`:
   - HTTP 401/403 → `AuthenticationException`;
   - outros → `InvalidResponseException`.
3. HTTP 2xx **e** `INS-0` → devolve `TransactionResponse`.
4. `INS-2` → `AuthenticationException`.
5. Qualquer outro caso → `ApiException`.

## Ordem recomendada de `catch`

Do mais específico para o mais genérico:

```php
try {
    $resposta = $mpesa->c2b($valor, $telefone, $ref, $refUnica);
} catch (ValidationException $e) {
    // dados inválidos; nada enviado. $e->field indica o campo.
} catch (AuthenticationException $e) {
    // credenciais erradas: alertar a equipa técnica.
} catch (ApiException $e) {
    // recusado pela M-Pesa. $e->responseCode, $e->isCancelledByCustomer(), ...
} catch (ConnectionException | InvalidResponseException $e) {
    // estado desconhecido: confirmar com query().
} catch (ConfigurationException $e) {
    // configuração em falta.
}
```

Ou capture apenas `MpesaException` se a aplicação tratar tudo da mesma forma.

## Atalhos de `ApiException`

| Método | Código |
|---|---|
| `isCancelledByCustomer()` | `INS-5` |
| `isRequestTimeout()` | `INS-9` |
| `isDuplicate()` | `INS-10` |
| `isInsufficientBalance()` | `INS-2006` |

Para outros códigos, compare `$e->responseCode` com as constantes de `Jjnguirazej\MpesaSdk\Responses\ResponseCode`. `ResponseCode::describe($codigo)` devolve a descrição oficial.

## Códigos da API

Tabela da documentação oficial. A coluna "SDK" indica a exceção lançada.

| HTTP | Código | Descrição | Constante | SDK |
|---|---|---|---|---|
| 200/201 | INS-0 | Request processed successfully | `SUCCESS` | resposta devolvida |
| 500 | INS-1 | Internal Error | `INTERNAL_ERROR` | `ApiException` |
| 401 | INS-2 | Invalid API Key | `INVALID_API_KEY` | `AuthenticationException` |
| 401 | INS-4 | User is not active | `USER_NOT_ACTIVE` | `ApiException` |
| 401 | INS-5 | Transaction cancelled by customer | `CANCELLED_BY_CUSTOMER` | `ApiException` |
| 401 | INS-6 | Transaction Failed | `TRANSACTION_FAILED` | `ApiException` |
| 408 | INS-9 | Request timeout | `REQUEST_TIMEOUT` | `ApiException` |
| 409 | INS-10 | Duplicate Transaction | `DUPLICATE_TRANSACTION` | `ApiException` |
| 400 | INS-13 | Invalid Shortcode Used | `INVALID_SHORTCODE` | `ApiException` |
| 400 | INS-14 | Invalid Reference Used | `INVALID_REFERENCE` | `ApiException` |
| 400 | INS-15 | Invalid Amount Used | `INVALID_AMOUNT` | `ApiException` |
| 503 | INS-16 | Unable to handle the request due to a temporary overloading | `TEMPORARY_OVERLOAD` | `ApiException` |
| 400 | INS-17 | Invalid Transaction Reference. Length Should Be Between 1 and 20. | `INVALID_TRANSACTION_REFERENCE_LENGTH` | evitado localmente (`ValidationException`) |
| 400 | INS-18 | Invalid TransactionID Used | `INVALID_TRANSACTION_ID` | `ApiException` |
| 400 | INS-19 | Invalid ThirdPartyReference Used | `INVALID_THIRD_PARTY_REFERENCE` | `ApiException` |
| 400 | INS-20 | Not All Parameters Provided. Please try again. | `MISSING_PARAMETERS` | `ApiException` |
| 400 | INS-21 | Parameter validations failed. Please try again. | `PARAMETER_VALIDATION_FAILED` | `ApiException` |
| 400 | INS-22 | Invalid Operation Type | `INVALID_OPERATION_TYPE` | `ApiException` |
| 400 | INS-23 | Unknown Status. Contact M-Pesa Support | `UNKNOWN_STATUS` | `ApiException` |
| 400 | INS-24 | Invalid InitiatorIdentifier Used | `INVALID_INITIATOR_IDENTIFIER` | `ApiException` |
| 400 | INS-25 | Invalid SecurityCredential Used | `INVALID_SECURITY_CREDENTIAL` | `ApiException` |
| 400 | INS-26 | Not authorized | `NOT_AUTHORIZED` | `ApiException` |
| 400 | INS-993 | Direct Debit Missing | `DIRECT_DEBIT_MISSING` | `ApiException` |
| 400 | INS-994 | Direct Debit Already Exists | `DIRECT_DEBIT_ALREADY_EXISTS` | `ApiException` |
| 400 | INS-995 | Customer's Profile Has Problems | `CUSTOMER_PROFILE_PROBLEM` | `ApiException` |
| 400 | INS-996 | Customer Account Status Not Active | `CUSTOMER_ACCOUNT_NOT_ACTIVE` | `ApiException` |
| 400 | INS-997 | Linking Transaction Not Found | `LINKING_TRANSACTION_NOT_FOUND` | `ApiException` |
| 400 | INS-998 | Invalid Market | `INVALID_MARKET` | `ApiException` |
| 400 | INS-2001 | Initiator authentication error. | `INITIATOR_AUTHENTICATION_ERROR` | `ApiException` |
| 400 | INS-2002 | Receiver invalid. | `INVALID_RECEIVER` | `ApiException` |
| 422 | INS-2006 | Insufficient balance | `INSUFFICIENT_BALANCE` | `ApiException` |
| 400 | INS-2051 | MSISDN invalid. | `INVALID_MSISDN` | `ApiException` |
| 400 | INS-2057 | Language code invalid. | `INVALID_LANGUAGE_CODE` | `ApiException` |

Note que HTTP 401 aparece tanto em falhas de autenticação (`INS-2`) como em transações recusadas (`INS-5`, `INS-6`). O SDK decide pelo código `INS-*`, não pelo HTTP.

Códigos que pedem nova tentativa mais tarde: `INS-1` e `INS-16`. Antes de repetir uma operação financeira, confirme com `query()` que a primeira não foi processada.

## Mensagens e logs

- As mensagens estão em português e incluem a operação, o código M-Pesa e o HTTP. Nunca incluem a chave de API, o token, a credencial de segurança, o identificador do iniciador nem o corpo da resposta.
- `ApiException::$response` contém o corpo JSON devolvido pela M-Pesa. Não o devolva ao cliente final; registe apenas os campos necessários.
- O SDK não escreve logs. A política de logging é da aplicação:

```php
Log::warning('M-Pesa recusou o pagamento.', [
    'operation' => $e->operation->value,
    'code' => $e->responseCode,
    'http' => $e->httpStatus,
]);
```
