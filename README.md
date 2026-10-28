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
16. [Contribuição](#16-contribuição)
17. [Licença e atribuição](#17-licença-e-atribuição)
18. [Exemplos completos](#18-exemplos-completos)

Documentação detalhada: [configuração](docs/configuracao.md) · [autenticação](docs/autenticacao.md) · [pagamentos](docs/pagamentos.md) · [erros](docs/tratamento-de-erros.md) · [segurança](docs/seguranca.md) · [changelog](CHANGELOG.md)

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
| Laravel | 11.x, 12.x ou 13.x |
| Composer | 2.x |

Testado localmente com PHP 8.3 contra Laravel 11.0.3, 11.57, 12.70 e 13.39 (Guzzle 7 e 8), e instalado numa aplicação Laravel 13 nova para validar a descoberta automática e a publicação da configuração. O PHP 8.2 e o 8.4 estão incluídos na matriz de integração contínua (`.github/workflows/tests.yml`).

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

Mais detalhes em [docs/configuracao.md](docs/configuracao.md).

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

Todos os métodos aceitam o valor como `int`, `float` ou `string` numérica com até 2 casas decimais (ex.: `10`, `10.5`, `'10.50'`). O valor é enviado como texto (`"10.5"`).

A `reference` (`input_TransactionReference`) deve ter entre 1 e 20 caracteres, como exige a API (`INS-17`). O SDK recusa referências mais longas antes de enviar o pedido.

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

Requer `MPESA_INITIATOR_IDENTIFIER` e `MPESA_SECURITY_CREDENTIAL`. Só transações bem-sucedidas podem ser revertidas.

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

Não repita um `c2b()` automaticamente após um erro de ligação. Use sempre `query()` primeiro. Detalhes em [docs/pagamentos.md](docs/pagamentos.md).

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

Mais em [docs/tratamento-de-erros.md](docs/tratamento-de-erros.md).

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

- Credenciais apenas no `.env`; nunca em código nem no repositório.
- Sempre HTTPS, com verificação de certificado ativa. A opção `verifySSL => false` do pacote original foi removida.
- Portas e caminhos fixos no código; o host só pode ser alterado pela configuração e é validado (sem protocolo, porta ou caminho).
- Nenhum pedido é repetido automaticamente; redirecionamentos são recusados.
- O SDK não escreve logs. Mensagens de exceção nunca incluem a chave de API, o token, a credencial de segurança ou o corpo da resposta.
- `var_dump`/`dd` da configuração oculta os segredos.
- Proteção contra duplicados: a M-Pesa recusa um `thirdPartyReference` repetido com `INS-10`. Use referências únicas por transação.
- Callbacks não são assinados pela M-Pesa: trate-os como não confiáveis e confirme com `query()`.

Ver [docs/seguranca.md](docs/seguranca.md).

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

## 16. Contribuição

- **Erros:** abra uma issue com versão do PHP/Laravel, passos para reproduzir e a exceção obtida. Nunca inclua credenciais nem dados reais de clientes.
- **Melhorias:** abra uma issue antes de mudanças grandes para discutir a abordagem.
- **Código:** faça fork, crie um branch, adicione testes e confirme que `composer test` passa. Siga PSR-12, comentários em português e identificadores em inglês.
- **Vulnerabilidades:** não abra issue pública; contacte o mantenedor diretamente pelo GitHub.

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
