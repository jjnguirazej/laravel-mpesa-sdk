# Configuração

## Como a configuração é carregada

1. O service provider junta `config/mpesa.php` do pacote com o da aplicação (se publicado).
2. Na primeira vez que o `MpesaClient` é pedido ao container, o array `config('mpesa')` é convertido em `MpesaConfig` e validado.
3. Se algo estiver errado, é lançada `ConfigurationException` com o nome da chave e da variável `.env`.

A validação é feita no primeiro uso (e não no arranque) para que comandos como `php artisan migrate` funcionem mesmo sem credenciais M-Pesa.

## Opções

### `environment` (`MPESA_ENV`)

`sandbox` (por omissão) ou `production`. Qualquer outro valor é recusado. Define o host:

| Ambiente | Host |
|---|---|
| `sandbox` | `api.sandbox.vm.co.mz` |
| `production` | `api.vm.co.mz` |

### `api_host` (`MPESA_API_HOST`)

Substitui o host do ambiente. Só aceita um nome de host (`exemplo.co.mz`). São recusados protocolos (`https://`), portas (`:18352`), caminhos e credenciais embutidas (`user@host`). O protocolo é sempre HTTPS e as portas são fixas por operação.

Use apenas se a Vodacom lhe indicar um host diferente.

### `api_key` (`MPESA_API_KEY`) e `public_key` (`MPESA_PUBLIC_KEY`)

Fornecidas no portal. A chave pública pode ser colada só com o conteúdo base64 (como aparece no portal) ou em formato PEM:

```env
MPESA_PUBLIC_KEY=MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA...
```

```env
MPESA_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA...
-----END PUBLIC KEY-----"
```

### `service_provider_code` (`MPESA_SERVICE_PROVIDER_CODE`)

Shortcode da sua entidade. Enviado como `input_ServiceProviderCode` e, no B2B, como `input_PrimaryPartyCode`. No sandbox o código de testes é normalmente `171717`.

Por compatibilidade com `samuelbie/mpesa`, `MPESA_PROVIDER_NUMBER` também é lido se `MPESA_SERVICE_PROVIDER_CODE` não existir. Ao contrário do pacote original, não há valor por omissão: um shortcode errado em produção não deve passar despercebido.

### `origin` (`MPESA_ORIGIN`)

Valor do cabeçalho `Origin`. Por omissão `*`, como no SDK original.

### `initiator_identifier` e `security_credential`

Necessários apenas para `reversal()`. Se faltarem, `reversal()` lança `ConfigurationException` antes de enviar o pedido; as restantes operações funcionam normalmente.

### `timeout` (`MPESA_TIMEOUT`) e `connect_timeout` (`MPESA_CONNECT_TIMEOUT`)

Em segundos, inteiros maiores que zero. Por omissão `120` e `10`.

O C2B só responde depois de o cliente reagir ao pedido de PIN. Um `timeout` curto faz com que a sua aplicação desista enquanto o cliente ainda está a confirmar, deixando a transação em estado desconhecido. Mantenha um valor generoso.

## Publicar a configuração

```bash
php artisan vendor:publish --tag=mpesa-config
```

Cria `config/mpesa.php` na aplicação. Só é necessário se quiser alterar a forma como os valores são lidos.

## Cache de configuração

Em produção, após alterar o `.env`:

```bash
php artisan config:clear
php artisan config:cache
```

## Várias contas M-Pesa

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
