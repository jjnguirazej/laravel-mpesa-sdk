# Tratamento de erros

## Hierarquia

```
RuntimeException
└── MpesaException
    ├── ConfigurationException
    ├── ValidationException            (field)
    ├── ConnectionException            (operation, isTimeout())
    ├── InvalidResponseException       (operation, httpStatus)
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

Para outros códigos, compare `$e->responseCode`. As constantes conhecidas estão em `Jjnguirazej\MpesaSdk\Responses\ResponseCode`. Confirme a lista completa de códigos no portal M-Pesa.

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
