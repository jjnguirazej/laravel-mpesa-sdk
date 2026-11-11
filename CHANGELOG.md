# Changelog

Todas as alterações relevantes deste pacote são documentadas aqui.
O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto usa [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.1.0] - 2026-11-11

### Adicionado

- Suporte a Laravel 10, para que aplicações existentes possam substituir `samuelbie/mpesa` sem atualizar primeiro a framework.
- Dependência explícita de `guzzlehttp/guzzle` (`^7.5|^8.0`), porque o `illuminate/http` 10 não a instala. Em Laravel 12 e 13 não muda nada: o `illuminate/http` já a exige.
- Laravel 10 na matriz de integração contínua.

### Notas

- Sem alterações de código nem de API. Em Laravel 12 e 13 o conjunto de dependências resolvido é o mesmo da versão 1.0.0.
- O Laravel 11 continua sem suporte.

## [1.0.0] - 2026-11-01

Primeira versão de `jjnguirazej/mpesa-sdk`, reescrita a partir de `samuelbie/mpesa` 1.6.0.

### Adicionado

- `MpesaClient` registado no container, com injeção de dependências e facade `Mpesa`.
- Configuração `MPESA_ENV` (`sandbox`/`production`) com hosts associados.
- Validação da configuração com mensagens claras (`ConfigurationException`).
- Exceções específicas: `ValidationException`, `ApiException`, `AuthenticationException`, `ConnectionException`, `InvalidResponseException`.
- `TransactionResponse` imutável, com `isCompleted()`, `status()` e `isAwaitingResult()` (modo assíncrono), e acesso à resposta original (`raw`).
- `ResponseCode` com a tabela oficial completa de códigos `INS-*` e `ResponseCode::describe()`.
- Enum `TransactionStatus` (`Completed`, `Cancelled`, `Expired`, `N/A`).
- `parseCallback()` / `CallbackResult` para o resultado assíncrono enviado ao Response URL, com `acknowledgement()` no formato documentado.
- Reversão total quando o valor é `null`.
- Validação local do limite de 20 caracteres de `input_TransactionReference`.
- Tempos limite configuráveis (`MPESA_TIMEOUT`, `MPESA_CONNECT_TIMEOUT`).
- Suporte a chave pública em formato PEM.
- Suite de testes com `Http::fake()` (115 testes).
- Documentação completa em português.

### Alterado

- Cliente HTTP do Laravel em vez de instanciar Guzzle diretamente.
- Uma resposta só é devolvida com HTTP 2xx e `INS-0`; tudo o resto lança exceção.
- Valores com mais de 2 casas decimais são recusados em vez de arredondados; o valor é enviado como texto.
- `MPESA_PROVIDER_NUMBER` passa a `MPESA_SERVICE_PROVIDER_CODE` (o nome antigo continua a ser lido) e deixa de ter valor por omissão.
- Requisitos: PHP 8.2+ e Laravel 12 ou 13.

### Corrigido

- B2B não funcionava (endpoint e método ausentes da configuração).
- Pedidos enviavam `form_params` e `json` ao mesmo tempo.
- Reversão enviava o valor como `input_Amount`; a documentação oficial define `input_ReversalAmount` (opcional).

### Removido

- Opção `verifySSL` (TLS passa a ser sempre verificado).
- Endpoints e métodos HTTP configuráveis por `.env`.
- `TransactionResponse::getResponse()` e as interfaces `ConfigInterface`, `TransactionInterface`, `TransactionResponseInterface`.

### Segurança

- Verificação de certificados TLS sempre ativa.
- Credenciais guardadas em objetos `Secret`: ocultas em `dd`, `var_dump`, `var_export` e `json_encode`; serialização proibida.
- Payload enviado à API e valores de configuração marcados com `#[SensitiveParameter]` (não aparecem em stack traces).
- `ConnectionException` não encadeia a exceção do Guzzle, que continha o cabeçalho `Authorization`.
- Credenciais da reversão ocultadas automaticamente no Telescope.
- Workflow de CI com permissões só de leitura.
- Mensagens de exceção sem credenciais, tokens ou corpos de resposta.
- Redirecionamentos HTTP recusados; host validado.
- `.env.example` apenas com valores fictícios.
