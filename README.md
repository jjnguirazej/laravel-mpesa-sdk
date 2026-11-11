# Laravel M-Pesa SDK

SDK Laravel para a API M-Pesa da **Vodacom Moçambique** (`api.vm.co.mz`).

```bash
composer require jjnguirazej/mpesa-sdk
```

---

## Índice

1. [Apresentação](#1-apresentação)
2. [Funcionalidades](#2-funcionalidades)
3. [Requisitos](#3-requisitos)
4. [Instalação](#4-instalação)
5. [Configuração](#5-configuração)
6. [Primeiro uso](#6-primeiro-uso)
7. [Integração com controllers](#7-integração-com-controllers)
8. [Exemplos práticos](#8-exemplos-práticos)
9. [Fluxo de pagamento](#9-fluxo-de-pagamento)
10. [Tratamento de erros](#10-tratamento-de-erros)
11. [Callbacks e webhooks](#11-callbacks-e-webhooks)
12. [Testes](#12-testes)
13. [Segurança](#13-segurança)
14. [Solução de problemas](#14-solução-de-problemas)
15. [Limitações conhecidas](#15-limitações-conhecidas)
16. [Contribuição e suporte](#16-contribuição-e-suporte)
17. [Licença e atribuição](#17-licença-e-atribuição)
18. [Exemplos completos](#18-exemplos-completos)

Histórico de versões: [CHANGELOG.md](CHANGELOG.md)

---

## 1. Apresentação

Este pacote permite que uma aplicação Laravel cobre clientes, transfira valores, reverta transações e consulte o estado de transações através da API M-Pesa da Vodacom Moçambique, sem ter de lidar com cifra RSA, cabeçalhos, portas ou formatos de resposta.

É uma reescrita do pacote [`samuelbie/mpesa`](https://github.com/Samuel-Bie/mpesa-laravel-sdk), de Samuel Bie, que por sua vez adaptou o [`abdulmueid/mpesa-php-api`](https://github.com/abdulmueid/mpesa-php-api). As operações suportadas são as mesmas; a reescrita foca-se em segurança, tratamento de erros, testes e facilidade de uso.

**Para quem:** programadores Laravel que integram pagamentos M-Pesa em Moçambique.

**O que o SDK não faz:** não guarda transações, não gere encomendas nem decide quando um pedido está pago. Isso é lógica da sua aplicação. O SDK fornece as operações da API de forma segura e previsível.

## 2. Funcionalidades

Implementado e testado (com respostas simuladas):

| Método | Operação M-Pesa | Endpoint |
|---|---|---|
| `c2b()` | Cobrança ao cliente (Customer to Business) | `POST :18352/ipg/v1x/c2bPayment/singleStage/` |
| `b2c()` | Pagamento a cliente (Business to Customer) | `POST :18345/ipg/v1x/b2cPayment/` |
| `b2b()` | Pagamento a outra entidade (Business to Business) | `POST :18349/ipg/v1x/b2bPayment/` |
| `reversal()` | Reversão de transação | `PUT :18354/ipg/v1x/reversal/` |
| `query()` | Consulta do estado de uma transação | `GET :18353/ipg/v1x/queryTransactionStatus/` |

Inclui também:

- Leitura do resultado assíncrono enviado para o Response URL (`parseCallback()`) e resposta de confirmação no formato documentado.
- Tabela completa de códigos `INS-*` e estados da transação (`Completed`, `Cancelled`, `Expired`, `N/A`).
- Geração automática do token de autenticação.
- Validação e normalização de números Vodacom (84/85) e valores.
- Exceções específicas para cada tipo de falha.
- Integração com o container do Laravel, facade opcional e descoberta automática.

Não implementado (ver [Limitações conhecidas](#15-limitações-conhecidas)): consulta do nome do cliente, C2B em duas fases, verificação criptográfica de callbacks (a M-Pesa não documenta assinatura).

## 3. Requisitos

| Componente | Versões |
|---|---|
| PHP | 8.2 ou superior, com as extensões `openssl` e `json` |
| Laravel | 10.x, 12.x ou 13.x |
| Composer | 2.x |

Testado com PHP 8.2, 8.3 e 8.4 contra Laravel 10, 12 e 13 (Guzzle 7 e 8) na integração contínua (`.github/workflows/tests.yml`), e instalado numa aplicação Laravel 13 nova para validar a descoberta automática e a publicação da configuração.

O Laravel 10 é aceite para que aplicações existentes possam substituir `samuelbie/mpesa` sem atualizar primeiro a framework. Também já terminou o período de correções de segurança, por isso recomenda-se planear a atualização para Laravel 12 ou 13.

O Laravel 11 não é suportado: terminou o período de correções de segurança e todas as suas versões têm avisos de segurança publicados. Atualize a aplicação para Laravel 12 ou 13.

Também é necessária uma conta no [portal de desenvolvedores M-Pesa](https://developer.mpesa.vm.co.mz) para obter as credenciais.

## 4. Instalação

```bash
composer require jjnguirazej/mpesa-sdk
```

O service provider e a facade `Mpesa` são registados automaticamente (package discovery). Não precisa de editar `bootstrap/providers.php`.

Para publicar o ficheiro de configuração (opcional):

```bash
php artisan vendor:publish --tag=mpesa-config
```

## 5. Configuração

Adicione ao `.env` da sua aplicação:

```env
MPESA_ENV=sandbox
MPESA_API_KEY=sua-chave-de-api
MPESA_PUBLIC_KEY=sua-chave-publica-base64
MPESA_SERVICE_PROVIDER_CODE=171717

# Apenas se usar reversões
MPESA_INITIATOR_IDENTIFIER=seu-initiator-identifier
MPESA_SECURITY_CREDENTIAL=sua-security-credential
```

| Variável | Obrigatória | Descrição |
|---|---|---|
| `MPESA_ENV` | não (`sandbox`) | `sandbox` ou `production`. |
| `MPESA_API_KEY` | sim | Chave de API do portal. |
| `MPESA_PUBLIC_KEY` | sim | Chave pública do portal (base64 ou PEM). |
| `MPESA_SERVICE_PROVIDER_CODE` | sim | Shortcode da sua entidade. Aceita também `MPESA_PROVIDER_NUMBER`. |
| `MPESA_ORIGIN` | não (`*`) | Cabeçalho `Origin`. |
| `MPESA_INITIATOR_IDENTIFIER` | só para reversões | Identificador do iniciador. |
| `MPESA_SECURITY_CREDENTIAL` | só para reversões | Credencial de segurança. |
| `MPESA_API_HOST` | não | Substitui o host do ambiente. Raramente necessário. |
| `MPESA_TIMEOUT` | não (`120`) | Tempo máximo de resposta, em segundos. |
| `MPESA_CONNECT_TIMEOUT` | não (`10`) | Tempo máximo de ligação, em segundos. |

**Sandbox:** use as credenciais da área de testes do portal e `MPESA_ENV=sandbox`. O host usado é `api.sandbox.vm.co.mz`.

**Produção:** use as credenciais de produção (são diferentes das de sandbox) e defina `MPESA_ENV=production`. O host passa a ser `api.vm.co.mz`. O pacote nunca usa produção por omissão.

As credenciais são validadas quando o `MpesaClient` é usado pela primeira vez. Se faltar algum valor, é lançada uma `ConfigurationException` com o nome da variável em falta.

### Como a configuração é carregada

1. O service provider junta `config/mpesa.php` do pacote com o da aplicação (se publicado).
2. Na primeira vez que o `MpesaClient` é pedido ao container, o array `config('mpesa')` é convertido em `MpesaConfig` e validado.
3. Se algo estiver errado, é lançada `ConfigurationException` com o nome da chave e da variável `.env`.

A validação é feita no primeiro uso (e não no arranque) para que comandos como `php artisan migrate` funcionem mesmo sem credenciais M-Pesa.

### Opções em detalhe

#### `environment` (`MPESA_ENV`)

`sandbox` (por omissão) ou `production`. Qualquer outro valor é recusado. Define o host:

| Ambiente | Host |
|---|---|
| `sandbox` | `api.sandbox.vm.co.mz` |
| `production` | `api.vm.co.mz` |

#### `api_host` (`MPESA_API_HOST`)

Substitui o host do ambiente. Só aceita um nome de host (`exemplo.co.mz`). São recusados protocolos (`https://`), portas (`:18352`), caminhos e credenciais embutidas (`user@host`). O protocolo é sempre HTTPS e as portas são fixas por operação.

Use apenas se a Vodacom lhe indicar um host diferente.

#### `api_key` (`MPESA_API_KEY`) e `public_key` (`MPESA_PUBLIC_KEY`)

Fornecidas no portal. A chave pública pode ser colada só com o conteúdo base64 (como aparece no portal) ou em formato PEM:

```env
MPESA_PUBLIC_KEY=MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA...
```

```env
MPESA_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA...
-----END PUBLIC KEY-----"
```

#### `service_provider_code` (`MPESA_SERVICE_PROVIDER_CODE`)

Shortcode da sua entidade. Enviado como `input_ServiceProviderCode` e, no B2B, como `input_PrimaryPartyCode`. No sandbox o código de testes é normalmente `171717`.

Por compatibilidade com `samuelbie/mpesa`, `MPESA_PROVIDER_NUMBER` também é lido se `MPESA_SERVICE_PROVIDER_CODE` não existir. Ao contrário do pacote original, não há valor por omissão: um shortcode errado em produção não deve passar despercebido.

#### `origin` (`MPESA_ORIGIN`)

Valor do cabeçalho `Origin`. Por omissão `*`, como no SDK original. A documentação oficial mostra `developer.mpesa.vm.co.mz` como exemplo; se a API recusar pedidos por causa da origem, use esse valor ou o indicado pela Vodacom.

#### `initiator_identifier` e `security_credential`

Necessários apenas para `reversal()`. Gerados pela Vodacom para identificar e verificar a sua entidade, juntamente com o shortcode. Se faltarem, `reversal()` lança `ConfigurationException` antes de enviar o pedido; as restantes operações funcionam normalmente.

#### `timeout` (`MPESA_TIMEOUT`) e `connect_timeout` (`MPESA_CONNECT_TIMEOUT`)

Em segundos, inteiros maiores que zero. Por omissão `120` e `10`.

O C2B só responde depois de o cliente reagir ao pedido de PIN. Um `timeout` curto faz com que a sua aplicação desista enquanto o cliente ainda está a confirmar, deixando a transação em estado desconhecido. Mantenha um valor generoso.

### Cache de configuração

Em produção, após alterar o `.env`:

```bash
php artisan config:clear
php artisan config:cache
```

### Várias contas M-Pesa

O container regista uma única configuração. Para usar outra conta, crie o cliente manualmente:

```php
use Illuminate\Http\Client\Factory;
use Jjnguirazej\MpesaSdk\Auth\BearerTokenGenerator;
use Jjnguirazej\MpesaSdk\Http\Transport;
use Jjnguirazej\MpesaSdk\MpesaClient;
use Jjnguirazej\MpesaSdk\MpesaConfig;

$config = MpesaConfig::fromArray(config('services.mpesa_loja2'));

$client = new MpesaClient(
    new Transport(app(Factory::class), $config, new BearerTokenGenerator($config)),
    $config,
);
```

### Autenticação

#### Como funciona

A API M-Pesa da Vodacom Moçambique não usa OAuth nem um endpoint para obter tokens. O token é calculado localmente:

1. A chave de API (`MPESA_API_KEY`) é cifrada com a chave pública RSA (`MPESA_PUBLIC_KEY`), usando padding PKCS#1 v1.5.
2. O resultado é codificado em base64.
3. É enviado no cabeçalho `Authorization: Bearer <token>`.

Isto é feito pela classe interna `Jjnguirazej\MpesaSdk\Auth\BearerTokenGenerator`, chamada automaticamente pelo `Transport` em cada pedido. Os controllers nunca veem o token.

#### Porque não há cache nem renovação

- A geração é local e rápida (uma operação RSA), sem pedido de rede.
- Não existe um prazo de validade documentado nem um endpoint de renovação.
- O padding PKCS#1 v1.5 é aleatório, por isso cada token é diferente; gerar um por pedido é o comportamento do SDK original e do `abdulmueid/mpesa-php-api`.

Por isso o SDK não usa a cache do Laravel para tokens: não haveria ganho e criaria mais um local onde um segredo ficaria guardado.

#### Erros

| Situação | Exceção |
|---|---|
| Chave pública ilegível | `ConfigurationException` (antes de qualquer pedido) |
| Falha ao cifrar | `ConfigurationException` |
| API recusa a chave (`INS-2`) | `AuthenticationException` |
| HTTP 401/403 sem corpo JSON | `AuthenticationException` |
| Credenciais do iniciador erradas (`INS-24`, `INS-25`, `INS-2001`) | `ApiException` (apenas em reversões) |

Atenção: a API usa HTTP 401 também para transações recusadas pelo cliente (`INS-5`, `INS-6`). Por isso o SDK decide pelo `output_ResponseCode`, e não só pelo código HTTP. Essas situações lançam `ApiException`, não `AuthenticationException`.

#### Causas comuns de `AuthenticationException`

- Credenciais de sandbox com `MPESA_ENV=production` (ou o contrário).
- Chave de API copiada com espaços ou aspas a mais.
- Chave pública de um ambiente diferente da chave de API.

## 6. Primeiro uso

```php
use Jjnguirazej\MpesaSdk\MpesaClient;

$mpesa = app(MpesaClient::class);

$resposta = $mpesa->c2b(
    amount: 10,
    msisdn: '841234567',
    reference: 'Pedido42',
    thirdPartyReference: 'PED42-001',
);

$resposta->transactionId;   // output_TransactionID
$resposta->conversationId;  // output_ConversationID
```

Ou com a facade:

```php
use Jjnguirazej\MpesaSdk\Facades\Mpesa;

$resposta = Mpesa::c2b(10, '841234567', 'Pedido42', 'PED42-001');
```

Se o método devolver uma resposta, a operação foi aceite pela M-Pesa (`INS-0`). Em qualquer outro caso é lançada uma exceção.

## 7. Integração com controllers

Injete o `MpesaClient` no construtor ou no método. O controller valida os dados, chama o SDK, trata as exceções e devolve a resposta da sua aplicação. Não precisa de lidar com tokens, cabeçalhos ou URLs.

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jjnguirazej\MpesaSdk\Exceptions\ApiException;
use Jjnguirazej\MpesaSdk\Exceptions\ConnectionException;
use Jjnguirazej\MpesaSdk\Exceptions\MpesaException;
use Jjnguirazej\MpesaSdk\Exceptions\ValidationException;
use Jjnguirazej\MpesaSdk\MpesaClient;

class PaymentController extends Controller
{
    public function __construct(
        private readonly MpesaClient $mpesa,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'phone' => ['required', 'string'],
        ]);

        // Referência única gerada pela aplicação (guarde-a antes de chamar a API).
        $reference = Str::upper(Str::random(12));

        try {
            $payment = $this->mpesa->c2b(
                amount: $validated['amount'],
                msisdn: $validated['phone'],
                reference: 'Loja'.$reference,
                thirdPartyReference: $reference,
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ApiException $e) {
            if ($e->isCancelledByCustomer()) {
                return response()->json(['message' => 'Pagamento cancelado no telemóvel.'], 402);
            }

            Log::warning('Pagamento M-Pesa recusado.', ['code' => $e->responseCode]);

            return response()->json(['message' => 'O pagamento não foi aceite.'], 402);
        } catch (ConnectionException $e) {
            // Estado desconhecido: não marque como falhado nem repita automaticamente.
            Log::error('Sem resposta da M-Pesa.', ['timeout' => $e->isTimeout()]);

            return response()->json(['message' => 'A verificar o pagamento. Tente consultar mais tarde.'], 202);
        } catch (MpesaException $e) {
            Log::error('Falha na integração M-Pesa.', ['exception' => $e::class]);

            return response()->json(['message' => 'Não foi possível processar o pagamento.'], 502);
        }

        return response()->json([
            'reference' => $reference,
            'transaction_id' => $payment->transactionId,
        ], 201);
    }
}
```

Boas práticas:

- Valide os dados com as regras do Laravel antes de chamar o SDK. O SDK volta a validar e lança `ValidationException` sem enviar nada à API.
- Nunca devolva ao cliente final `$e->response` nem mensagens internas; registe apenas o código (`responseCode`).
- Não envie o host, o shortcode ou credenciais a partir do pedido HTTP. Tudo vem da configuração.

## 8. Exemplos práticos

### Referências

| Referência | Quem gera | Uso |
|---|---|---|
| `reference` (`input_TransactionReference`) | A aplicação | Descrição/identificador visível da transação. Entre 1 e 20 caracteres (`INS-17`). |
| `thirdPartyReference` (`input_ThirdPartyReference`) | A aplicação | Identificador único da aplicação. Repetido = `INS-10`. |
| `transactionId` (`output_TransactionID`) | M-Pesa | Identificador da transação na M-Pesa. Usado em `reversal()` e `query()`. |
| `conversationId` (`output_ConversationID`) | M-Pesa | Identificador do pedido na M-Pesa. Usado em `query()` e para ligar o callback assíncrono (`input_OriginalConversationID`). |

Guarde sempre a `thirdPartyReference` **antes** de chamar a API e os identificadores M-Pesa depois.

### Valores

- Aceita `int`, `float` ou `string` numérica, maior que zero, com até 2 casas decimais.
- Valores com mais casas são **recusados**, não arredondados (o SDK original arredondava).
- Enviado como texto: `10` → `"10"`, `10.50` → `"10.5"`.

### Números de telefone

Aceita números Vodacom (84 e 85) nos formatos `84XXXXXXX`, `25884XXXXXXX`, `+25884XXXXXXX`, `0025884XXXXXXX`, com espaços ou hífens. Enviado como `25884XXXXXXX`.

### C2B — cobrar um cliente

```php
$resposta = $mpesa->c2b(
    amount: 250,
    msisdn: '+258841234567',          // 84/85, com ou sem 258, +258 ou 00258
    reference: 'Fatura1024',          // input_TransactionReference (1 a 20 caracteres)
    thirdPartyReference: 'FT1024A',   // input_ThirdPartyReference (única)
);
```

O cliente recebe no telemóvel um pedido para introduzir o PIN. A API só responde depois da confirmação, recusa ou expiração.

### B2C — pagar a um cliente

```php
$resposta = $mpesa->b2c(
    amount: 500,
    msisdn: '851234567',
    reference: 'Comissao0425',
    thirdPartyReference: 'COM0425',
);
```

### B2B — pagar a outra entidade

```php
$resposta = $mpesa->b2b(
    amount: 1500,
    receiverPartyCode: '979797',      // shortcode de quem recebe
    reference: 'Fornecedor88',
    thirdPartyReference: 'FORN88',
);
```

O shortcode de origem (`input_PrimaryPartyCode`) é o `MPESA_SERVICE_PROVIDER_CODE`.

### Reversão

```php
// Reversão parcial (ou total) de um valor: envia input_ReversalAmount
$resposta = $mpesa->reversal(
    amount: 250,
    transactionId: 'abc123xyz',       // output_TransactionID da transação original
    thirdPartyReference: 'REV1024',
);

// Reversão total: sem valor, a API reverte a transação completa
$resposta = $mpesa->reversal(null, 'abc123xyz', 'REV1025');
```

Reverte uma transação **bem-sucedida**: a M-Pesa retira o valor da carteira de quem recebeu e devolve-o a quem iniciou a transação original. Com valor, é enviado `input_ReversalAmount` (reversão parcial ou total desse montante); com `null`, o campo é omitido e a API tenta a reversão total.

Requer `MPESA_INITIATOR_IDENTIFIER` e `MPESA_SECURITY_CREDENTIAL`. Credenciais do iniciador erradas devolvem `INS-24`, `INS-25` ou `INS-2001`.

### Consulta de estado

```php
$estado = $mpesa->query(
    queryReference: 'abc123xyz',      // output_TransactionID ou output_ConversationID
    thirdPartyReference: 'Q1024',
);

$estado->transactionStatus;  // texto original: "Completed", "Cancelled", "Expired" ou "N/A"
$estado->status();           // enum TransactionStatus, ou null se o valor não for documentado
$estado->isCompleted();      // true apenas se o estado for "Completed"
```

### Resposta (`TransactionResponse`)

| Propriedade | Campo da API | Notas |
|---|---|---|
| `operation` | — | Enum `Operation` (`c2b`, `b2c`, ...). |
| `httpStatus` | — | Código HTTP (200 ou 201). |
| `responseCode` | `output_ResponseCode` | Sempre `INS-0` numa resposta devolvida. |
| `responseDescription` | `output_ResponseDesc` | |
| `transactionId` | `output_TransactionID` | `null` se ausente. |
| `conversationId` | `output_ConversationID` | `null` se ausente. |
| `thirdPartyReference` | `output_ThirdPartyReference` | `null` se ausente. |
| `transactionStatus` | `output_ResponseTransactionStatus` | Apenas em `query()`. |
| `raw` | (corpo completo) | Resposta original da API, sem alterações. |

Métodos: `isSuccessful()`, `isCompleted()`, `status()` e `isAwaitingResult()` (ver [Fluxo de pagamento](#9-fluxo-de-pagamento)).

`toArray()` e `json_encode()` devolvem os campos normalizados (sem `raw`). Use `raw` se precisar de um campo que o SDK não mapeia.

## 9. Fluxo de pagamento

1. **Gere e guarde** uma referência única (`thirdPartyReference`) e o pedido com estado "pendente", antes de chamar a API.
2. **Chame `c2b()`.** A API envia um pedido de PIN ao cliente e aguarda.
3. **Resultado:**
   - Resposta devolvida (`INS-0`) com `transactionId`: modo síncrono, a M-Pesa reporta a transação como bem-sucedida. Guarde `transactionId` e `conversationId`.
   - Resposta devolvida (`INS-0`) com `isAwaitingResult() === true`: modo assíncrono. O pedido foi **apenas aceite**; o resultado final chega ao seu Response URL (ver [Callbacks](#11-callbacks-e-webhooks)) ou por `query()` com o `conversationId`. Mantenha o pedido pendente.
   - `ApiException`: a M-Pesa recusou (cliente cancelou, saldo insuficiente, referência duplicada, etc.). O pagamento não foi feito.
   - `ConnectionException`: **estado desconhecido.** A API pode ter processado o pagamento. Mantenha o pedido "pendente" e use `query()` para confirmar.
4. **Reconcilie** pedidos pendentes com `query()` (por exemplo num job agendado), usando o `transactionId`/`conversationId` quando os tiver.

Não repita um `c2b()` automaticamente após um erro de ligação. Use sempre `query()` primeiro.

### Estados possíveis de um C2B

| Resultado da chamada | Significado | O que fazer |
|---|---|---|
| `TransactionResponse` (`INS-0`) com `transactionId` | Modo síncrono: a M-Pesa reporta sucesso. | Marcar como pago; guardar `transactionId`. |
| `TransactionResponse` com `isAwaitingResult()` | Modo assíncrono: pedido só aceite. | Manter pendente; guardar `conversationId`; aguardar callback ou usar `query()`. |
| `ApiException` | A M-Pesa recusou ou a transação falhou. | Marcar como falhado; mostrar mensagem ao cliente. |
| `ApiException::isRequestTimeout()` (`INS-9`) | O cliente não confirmou a tempo. | Marcar como falhado; permitir nova tentativa com nova referência. |
| `ConnectionException` | Sem resposta: **estado desconhecido**. | Manter pendente e confirmar com `query()`. |
| `InvalidResponseException` | Resposta ilegível: **estado desconhecido**. | Manter pendente e confirmar com `query()`. |
| `ValidationException` / `ConfigurationException` | Nada foi enviado. | Corrigir dados ou configuração. |

O SDK nunca devolve uma resposta quando o código não é `INS-0` ou o HTTP não é 2xx.

### Iniciar não é confirmar

Uma resposta HTTP bem-sucedida, por si só, não prova o pagamento. O SDK exige **as duas** condições: HTTP 2xx e `output_ResponseCode = INS-0`. Mesmo assim, recomenda-se reconciliar periodicamente com `query()`, sobretudo para pedidos que ficaram em estado desconhecido.

### Consultar o estado

```php
$estado = $mpesa->query($transactionIdOuConversationId, 'Q'.Str::random(10));

use Jjnguirazej\MpesaSdk\Responses\TransactionStatus;

match ($estado->status()) {
    TransactionStatus::Completed => $pedido->marcarComoPago(),
    TransactionStatus::Cancelled, TransactionStatus::Expired => $pedido->marcarComoFalhado(),
    TransactionStatus::NotAvailable, null => null, // sem informação: tentar mais tarde
};
```

- `queryReference`: `transactionId` ou `conversationId` da transação original.
- `thirdPartyReference`: referência desta consulta.
- Estados documentados (`output_ResponseTransactionStatus`): `Completed`, `Cancelled`, `Expired`, `N/A`. `status()` devolve o enum `TransactionStatus`, ou `null` para valores não documentados.
- `isCompleted()` só devolve `true` para `Completed`. Estados desconhecidos nunca contam como pagos.
- O texto original fica em `$estado->transactionStatus`.

### Modo assíncrono

Se a conta estiver em modo assíncrono, a resposta imediata só contém `output_ConversationID`, `output_ThirdPartyReference`, `output_ResponseCode` e `output_ResponseDesc`. `INS-0` significa apenas "pedido aceite". `TransactionResponse::isAwaitingResult()` devolve `true` nesse caso.

O resultado final chega ao Response URL (ver [Callbacks e webhooks](#11-callbacks-e-webhooks)) com `input_ResultCode = "0"` em caso de sucesso, e pode ser confirmado com `query($conversationId, ...)`.

### Repetições e duplicados

- O SDK **não repete** pedidos automaticamente.
- A M-Pesa recusa uma `thirdPartyReference` repetida com `INS-10` (`ApiException::isDuplicate()`). Se reutilizar a mesma referência ao repetir um pagamento em estado desconhecido, a M-Pesa impede a cobrança dupla; mas um `INS-10` não diz se a primeira tentativa foi concluída — confirme com `query()`.
- O SDK não inventa um mecanismo de idempotência próprio.

## 10. Tratamento de erros

Todas as exceções estendem `Jjnguirazej\MpesaSdk\Exceptions\MpesaException`.

| Exceção | Quando | Pedido enviado? |
|---|---|---|
| `ConfigurationException` | Configuração em falta ou inválida, chave pública ilegível. | Não |
| `ValidationException` | Valor, número ou referência inválidos. Propriedade `field`. | Não |
| `ApiException` | A API respondeu com código diferente de `INS-0`. Propriedades `responseCode`, `responseDescription`, `httpStatus`, `operation`, `response`. | Sim |
| `AuthenticationException` | Subclasse de `ApiException`: credenciais recusadas (`INS-2` ou HTTP 401/403 sem corpo). | Sim |
| `ConnectionException` | Falha de rede ou tempo limite. `isTimeout()`. **Estado da transação desconhecido.** | Talvez |
| `InvalidResponseException` | Resposta sem JSON válido ou sem `output_ResponseCode`. `httpStatus`. | Sim |
| `InvalidCallbackException` | Callback recebido sem os campos obrigatórios. | — |

Atalhos em `ApiException`: `isCancelledByCustomer()` (INS-5), `isRequestTimeout()` (INS-9), `isDuplicate()` (INS-10), `isInsufficientBalance()` (INS-2006).

Todos os códigos da documentação oficial estão em `ResponseCode` (constantes e `ResponseCode::describe($codigo)`).

### Hierarquia

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

### Como o SDK decide

1. Erro de rede → `ConnectionException`.
2. Corpo sem JSON válido ou sem `output_ResponseCode`:
   - HTTP 401/403 → `AuthenticationException`;
   - outros → `InvalidResponseException`.
3. HTTP 2xx **e** `INS-0` → devolve `TransactionResponse`.
4. `INS-2` → `AuthenticationException`.
5. Qualquer outro caso → `ApiException`.

### Ordem recomendada de `catch`

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

### Códigos da API

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

### Mensagens e logs

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

## 11. Callbacks e webhooks

Quando a conta funciona em modo assíncrono, a M-Pesa responde de imediato com `output_ConversationID` (sem `output_TransactionID`) e, quando a transação termina, envia o resultado por `POST` para o **Response URL** configurado em *My Account* no portal. O seu listener deve responder com uma confirmação.

```php
// routes/api.php (sem proteção CSRF)
use App\Http\Controllers\MpesaCallbackController;

Route::post('/mpesa/callback/{token}', MpesaCallbackController::class);
```

```php
<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jjnguirazej\MpesaSdk\Exceptions\InvalidCallbackException;
use Jjnguirazej\MpesaSdk\MpesaClient;

class MpesaCallbackController extends Controller
{
    public function __invoke(Request $request, string $token, MpesaClient $mpesa): JsonResponse
    {
        // Caminho secreto: a M-Pesa não assina os callbacks.
        abort_unless(hash_equals((string) config('services.mpesa.callback_token'), $token), 404);

        try {
            $result = $mpesa->parseCallback($request->json()->all());
        } catch (InvalidCallbackException) {
            return response()->json(['message' => 'Pedido inválido.'], 400);
        }

        // Idempotência: o mesmo resultado pode chegar mais de uma vez.
        $payment = Payment::where('conversation_id', $result->originalConversationId)
            ->where('status', 'pending')
            ->first();

        if ($payment !== null) {
            // Não confie apenas no callback: confirme junto da API.
            ProcessMpesaResult::dispatch($payment->id);
        }

        return response()->json($result->acknowledgement());
    }
}
```

Campos disponíveis em `CallbackResult`: `originalConversationId`, `thirdPartyReference`, `transactionId` (reversões), `resultCode` (`"0"` = sucesso), `resultDescription`, `transactionStatus`, `raw`. Métodos: `isSuccessful()`, `status()`, `acknowledgement()`.

**Segurança:** a documentação da M-Pesa não define assinatura nem outro mecanismo para provar a origem do callback. Por isso:

- use um caminho secreto e HTTPS;
- processe cada `originalConversationId` uma única vez;
- **confirme o estado com `query()`** antes de entregar bens ou serviços;
- se a Vodacom lhe indicar os IPs de origem, restrinja o acesso a esses IPs.

## 12. Testes

```bash
composer install
composer test        # ou: vendor/bin/phpunit
```

Os testes usam `Http::fake()` e um par de chaves RSA gerado em memória. Não fazem pedidos reais nem precisam de credenciais.

Para testar a sua aplicação sem chamar a M-Pesa:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'api.sandbox.vm.co.mz:18352/*' => Http::response([
        'output_ResponseCode' => 'INS-0',
        'output_ResponseDesc' => 'Request processed successfully',
        'output_TransactionID' => 'abc123xyz',
        'output_ConversationID' => 'conv123',
        'output_ThirdPartyReference' => 'PED42-001',
    ], 201),
]);
```

## 13. Segurança

### Credenciais

- Guarde `MPESA_API_KEY`, `MPESA_INITIATOR_IDENTIFIER` e `MPESA_SECURITY_CREDENTIAL` apenas no `.env` (ou num gestor de segredos). Nunca no código nem no Git.
- Use credenciais diferentes para sandbox e produção. O pacote nunca muda para produção sem `MPESA_ENV=production`.
- O `.env.example` do pacote contém apenas valores fictícios.
- A chave de API e as credenciais do iniciador ficam em objetos `Secret`, fora das propriedades da configuração. Não aparecem em `dd()`, `dump()`, `var_dump()`, `print_r()`, `var_export()` nem `json_encode()`.
- A configuração não pode ser serializada (lança `LogicException`). Isto impede que as credenciais acabem numa fila, cache ou sessão. Em jobs, resolva o `MpesaClient` no método `handle()`, nunca no construtor:

```php
public function handle(MpesaClient $mpesa): void
{
    $mpesa->query($this->transactionId, $this->reference);
}
```

- O token Bearer é a chave de API cifrada e não expira. Quem obtiver um cabeçalho `Authorization` consegue usá-lo como se tivesse a chave. Trate-o como a própria chave.
- Com `php artisan config:cache`, os valores do `.env` ficam em `bootstrap/cache/config.php`. Garanta que esse ficheiro não é público nem vai para o Git (comportamento normal do Laravel).

### Transporte

- Sempre HTTPS, com verificação do certificado (`verify => true`). Não existe opção para a desligar.
- Redirecionamentos HTTP são recusados.
- Portas e caminhos estão fixos no código (`Operation`). Só o host pode ser alterado, pela configuração, e é validado como nome de host simples. Nenhum dado vindo de um pedido HTTP da aplicação influencia o destino.
- Tempos limite de ligação e de resposta configuráveis.

### Repetições e duplicados

- Nenhum pedido é repetido automaticamente. Repetir um pagamento sem saber o resultado do primeiro pode cobrar duas vezes.
- Use uma `thirdPartyReference` única por transação; a M-Pesa recusa repetições com `INS-10`.

### Integridade do pagamento

- Sucesso exige HTTP 2xx **e** `INS-0`. Qualquer outro caso lança exceção.
- Respostas malformadas nunca são tratadas como sucesso.
- `isCompleted()` só é verdadeiro para o estado `"Completed"`; estados desconhecidos não contam como pagos.
- Erros de rede e respostas ilegíveis são "estado desconhecido" e devem ser confirmados com `query()`.

### Logs e exceções

- O SDK não regista logs.
- Mensagens de exceção não contêm credenciais, tokens nem corpos de resposta.
- Os argumentos com credenciais (payload enviado à API, valores de configuração) usam `#[SensitiveParameter]`, por isso não aparecem nas stack traces, mesmo com `zend.exception_ignore_args=0` ou em ferramentas como Sentry e Flare.
- `ConnectionException` não encadeia a exceção original do Guzzle (`getPrevious()` é `null`), porque esta contém o pedido completo com o cabeçalho `Authorization`. Só o código de erro cURL é preservado (`$e->curlError`).
- **Telescope:** se estiver instalado, o SDK marca automaticamente `input_SecurityCredential` e `input_InitiatorIdentifier` como ocultos (o `Authorization` já é oculto pelo Telescope).
- **Outras ferramentas:** o cliente HTTP do Laravel emite os eventos `RequestSending` e `ResponseReceived` com o pedido completo. Se a sua aplicação ou outra ferramenta de monitorização escutar esses eventos ou registar pedidos de saída, oculte o cabeçalho `Authorization` e os campos `input_SecurityCredential` e `input_InitiatorIdentifier`.
- Evite registar `ApiException::$response` completo ou números de telefone dos clientes sem necessidade.

### Callbacks assíncronos

A documentação da M-Pesa não define assinatura, segredo partilhado nem outro mecanismo para provar que um pedido no Response URL veio da M-Pesa. O SDK não inventa um. `parseCallback()` apenas valida o formato.

Recomendações:

- Use HTTPS e um caminho com um segredo aleatório (ex.: `/mpesa/callback/{token}`), comparado com `hash_equals()`.
- Restrinja por IP se a Vodacom lhe indicar os endereços de origem.
- Processe cada `input_OriginalConversationID` uma única vez (índice único na base de dados) para resistir a repetições.
- Nunca entregue bens apenas com base no callback: confirme com `query()`.
- Responda sempre com `acknowledgement()` depois de registar o resultado, para que a M-Pesa não reenvie.

### Alterações em relação ao `samuelbie/mpesa`

| Antes | Agora |
|---|---|
| `verifySSL => false` (TLS sem verificação) | Verificação sempre ativa |
| `.env.example` com chaves com aspeto real | Apenas valores fictícios |
| Erros de rede devolviam uma resposta com estado HTTP 460 inventado | `ConnectionException` |
| Erros 4xx/5xx devolvidos como resposta normal | `ApiException` |
| Endpoints e métodos HTTP alteráveis por `.env` | Fixos no código |
| Shortcode `171717` por omissão | Obrigatório |
| Valores arredondados silenciosamente | Recusados se tiverem mais de 2 casas decimais |
## 14. Solução de problemas

| Sintoma | Causa provável | Solução |
|---|---|---|
| `ConfigurationException: ... MPESA_API_KEY` | Variável em falta. | Defina-a no `.env` e execute `php artisan config:clear`. |
| `ConfigurationException` sobre a chave pública | Chave cortada ou com aspas/espaços extra. | Copie a chave completa do portal; aceita base64 ou PEM. |
| `AuthenticationException` (`INS-2`) | Chave de API errada ou credenciais de outro ambiente. | Confirme `MPESA_ENV` e as credenciais desse ambiente. |
| `ConnectionException` imediata | Firewall a bloquear as portas 18345–18354. | Libere tráfego de saída para essas portas. |
| `ConnectionException` com `isTimeout()` no C2B | Cliente demorou a confirmar ou rede lenta. | Consulte com `query()`; ajuste `MPESA_TIMEOUT`. |
| `ApiException` `INS-10` | `thirdPartyReference` repetida. | Gere uma referência nova por transação. |
| `ApiException` `INS-5` / `INS-9` | Cliente cancelou / não respondeu a tempo. | Peça ao cliente para tentar de novo. |
| Configuração ignorada | Cache de configuração. | `php artisan config:clear`. |
| Em produção, só funciona em sandbox | Origem/IP não autorizados ou credenciais de sandbox. | Confirme no portal e com a Vodacom. |

## 15. Limitações conhecidas

- **Testes apenas simulados.** O pacote não foi validado contra a API real (sandbox ou produção). Teste no sandbox antes de usar em produção.
- **Documentação oficial parcial.** Foram confirmados com a documentação do portal: a tabela de códigos `INS-*`, os estados da transação, o formato da reversão (`input_ReversalAmount` opcional) e o formato dos callbacks assíncronos. Endpoints e campos de C2B, B2C, B2B e consulta seguem o SDK original e coincidem com outros SDKs públicos.
- **Callbacks sem assinatura.** A M-Pesa não documenta forma de verificar a origem do callback; o SDK só interpreta o conteúdo.
- **`isAwaitingResult()`** deteta o modo assíncrono pela ausência de `output_TransactionID`, que é o formato documentado da resposta assíncrona. Confirme o modo da sua conta no portal.
- **Cabeçalho `Origin`:** o SDK envia `*` por omissão (como o SDK original); a documentação mostra `developer.mpesa.vm.co.mz` como exemplo. Ajuste `MPESA_ORIGIN` se a sua conta o exigir.
- **Limites de referências:** só o limite de 20 caracteres de `input_TransactionReference` é validado localmente; outras regras são validadas pela API.
- Não implementado: consulta de nome do cliente (`queryCustomerName`), C2B em duas fases, outros mercados M-Pesa.

## 16. Contribuição e suporte

### Suporte

Para dúvidas, ajuda na integração ou suporte:

- **Telefone:** [+258 86 157 6768](tel:+258861576768)
- **Email:** [contact@jorgenguiraze.com](mailto:contact@jorgenguiraze.com)
- **Issues:** [github.com/jjnguirazej/laravel-mpesa-sdk/issues](https://github.com/jjnguirazej/laravel-mpesa-sdk/issues)

Nunca envie credenciais M-Pesa (chave de API, credencial de segurança) por telefone, email ou issue.

### Contribuição

- **Erros:** abra uma issue com versão do PHP/Laravel, passos para reproduzir e a exceção obtida. Nunca inclua credenciais nem dados reais de clientes.
- **Melhorias:** abra uma issue antes de mudanças grandes para discutir a abordagem.
- **Código:** faça fork, crie um branch, adicione testes e confirme que `composer test` passa. Siga PSR-12, comentários em português e identificadores em inglês.
- **Vulnerabilidades:** não abra issue pública; envie os detalhes para [contact@jorgenguiraze.com](mailto:contact@jorgenguiraze.com).

## 17. Licença e atribuição

Licença [MIT](LICENSE).

Este pacote deriva de:

- [`samuelbie/mpesa`](https://github.com/Samuel-Bie/mpesa-laravel-sdk) — Samuel Bie (MIT).
- [`abdulmueid/mpesa-php-api`](https://github.com/abdulmueid/mpesa-php-api) — Abdul Mueid Akhtar (MIT), incluindo a normalização de MSISDN de Kishan Nareshpal Jadav.

Os avisos de copyright originais estão preservados no ficheiro `LICENSE`. Este pacote não é afiliado à Vodacom.

## 18. Exemplos completos

### Serviço de pagamentos com reconciliação

```php
<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Str;
use Jjnguirazej\MpesaSdk\Exceptions\ApiException;
use Jjnguirazej\MpesaSdk\Exceptions\ConnectionException;
use Jjnguirazej\MpesaSdk\MpesaClient;
use Jjnguirazej\MpesaSdk\Responses\TransactionStatus;

class MpesaPaymentService
{
    public function __construct(private readonly MpesaClient $mpesa) {}

    public function charge(Payment $payment): Payment
    {
        // 1. Guardar a referência antes de chamar a API.
        $payment->update([
            'third_party_reference' => $payment->third_party_reference ?? Str::upper(Str::random(12)),
            'status' => 'pending',
        ]);

        try {
            $response = $this->mpesa->c2b(
                amount: $payment->amount,
                msisdn: $payment->phone,
                reference: 'Pedido'.$payment->order_id,
                thirdPartyReference: $payment->third_party_reference,
            );

            $payment->update([
                // Modo assíncrono: só aceite; o resultado chega pelo callback ou por query().
                'status' => $response->isAwaitingResult() ? 'pending' : 'paid',
                'transaction_id' => $response->transactionId,
                'conversation_id' => $response->conversationId,
            ]);
        } catch (ApiException $e) {
            $payment->update(['status' => 'failed', 'failure_code' => $e->responseCode]);
        } catch (ConnectionException) {
            // Mantém "pending"; o job de reconciliação confirma mais tarde.
        }

        return $payment;
    }

    /**
     * Executar num job agendado para pagamentos que ficaram pendentes.
     */
    public function reconcile(Payment $payment): void
    {
        $reference = $payment->transaction_id ?? $payment->conversation_id;

        if ($payment->status !== 'pending' || $reference === null) {
            return;
        }

        try {
            $result = $this->mpesa->query($reference, 'Q'.Str::upper(Str::random(10)));
        } catch (ApiException|ConnectionException) {
            return; // tenta de novo na próxima execução
        }

        match ($result->status()) {
            TransactionStatus::Completed => $payment->update(['status' => 'paid']),
            TransactionStatus::Cancelled, TransactionStatus::Expired => $payment->update(['status' => 'failed']),
            default => null, // N/A ou desconhecido: mantém pendente
        };
    }
}
```

> Nota: se a ligação falhar no `c2b()`, a aplicação pode não ter `transactionId` nem `conversationId`. Nesse caso confirme o estado no portal M-Pesa ou com a Vodacom antes de cobrar novamente.

### Teste da aplicação com `Http::fake`

```php
use Illuminate\Support\Facades\Http;

public function test_pagamento_recusado_pelo_cliente(): void
{
    Http::fake(['*' => Http::response([
        'output_ResponseCode' => 'INS-5',
        'output_ResponseDesc' => 'Transaction cancelled by customer',
    ], 401)]);

    $this->postJson('/payments', ['amount' => 10, 'phone' => '841234567'])
        ->assertStatus(402);
}
```
