# Autenticação

## Como funciona

A API M-Pesa da Vodacom Moçambique não usa OAuth nem um endpoint para obter tokens. O token é calculado localmente:

1. A chave de API (`MPESA_API_KEY`) é cifrada com a chave pública RSA (`MPESA_PUBLIC_KEY`), usando padding PKCS#1 v1.5.
2. O resultado é codificado em base64.
3. É enviado no cabeçalho `Authorization: Bearer <token>`.

Isto é feito pela classe interna `Jjnguirazej\MpesaSdk\Auth\BearerTokenGenerator`, chamada automaticamente pelo `Transport` em cada pedido. Os controllers nunca veem o token.

## Porque não há cache nem renovação

- A geração é local e rápida (uma operação RSA), sem pedido de rede.
- Não existe um prazo de validade documentado nem um endpoint de renovação.
- O padding PKCS#1 v1.5 é aleatório, por isso cada token é diferente; gerar um por pedido é o comportamento do SDK original e do `abdulmueid/mpesa-php-api`.

Por isso o SDK não usa a cache do Laravel para tokens: não haveria ganho e criaria mais um local onde um segredo ficaria guardado.

## Erros

| Situação | Exceção |
|---|---|
| Chave pública ilegível | `ConfigurationException` (antes de qualquer pedido) |
| Falha ao cifrar | `ConfigurationException` |
| API recusa a chave (`INS-2`) | `AuthenticationException` |
| HTTP 401/403 sem corpo JSON | `AuthenticationException` |

Atenção: a API usa HTTP 401 também para transações recusadas pelo cliente (`INS-5`, `INS-6`). Por isso o SDK decide pelo `output_ResponseCode`, e não só pelo código HTTP. Essas situações lançam `ApiException`, não `AuthenticationException`.

## Causas comuns de `AuthenticationException`

- Credenciais de sandbox com `MPESA_ENV=production` (ou o contrário).
- Chave de API copiada com espaços ou aspas a mais.
- Chave pública de um ambiente diferente da chave de API.
