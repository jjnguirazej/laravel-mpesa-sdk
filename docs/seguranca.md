# Segurança

## Credenciais

- Guarde `MPESA_API_KEY`, `MPESA_INITIATOR_IDENTIFIER` e `MPESA_SECURITY_CREDENTIAL` apenas no `.env` (ou num gestor de segredos). Nunca no código nem no Git.
- Use credenciais diferentes para sandbox e produção. O pacote nunca muda para produção sem `MPESA_ENV=production`.
- O `.env.example` do pacote contém apenas valores fictícios.
- `MpesaConfig` oculta os segredos em `var_dump()`, `dd()` e `print_r()`, e os parâmetros sensíveis usam `#[SensitiveParameter]` para não aparecerem em stack traces.

## Transporte

- Sempre HTTPS, com verificação do certificado (`verify => true`). Não existe opção para a desligar.
- Redirecionamentos HTTP são recusados.
- Portas e caminhos estão fixos no código (`Operation`). Só o host pode ser alterado, pela configuração, e é validado como nome de host simples. Nenhum dado vindo de um pedido HTTP da aplicação influencia o destino.
- Tempos limite de ligação e de resposta configuráveis.

## Repetições e duplicados

- Nenhum pedido é repetido automaticamente. Repetir um pagamento sem saber o resultado do primeiro pode cobrar duas vezes.
- Use uma `thirdPartyReference` única por transação; a M-Pesa recusa repetições com `INS-10`.

## Integridade do pagamento

- Sucesso exige HTTP 2xx **e** `INS-0`. Qualquer outro caso lança exceção.
- Respostas malformadas nunca são tratadas como sucesso.
- `isCompleted()` só é verdadeiro para o estado `"Completed"`; estados desconhecidos não contam como pagos.
- Erros de rede e respostas ilegíveis são "estado desconhecido" e devem ser confirmados com `query()`.

## Logs e exceções

- O SDK não regista logs.
- Mensagens de exceção não contêm credenciais, tokens nem corpos de resposta.
- Evite registar `ApiException::$response` completo ou números de telefone dos clientes sem necessidade.

## Callbacks

O SDK não expõe endpoints nem processa callbacks. Se a sua aplicação receber notificações de outra origem, valide-as segundo a documentação oficial da Vodacom.

## Alterações em relação ao `samuelbie/mpesa`

| Antes | Agora |
|---|---|
| `verifySSL => false` (TLS sem verificação) | Verificação sempre ativa |
| `.env.example` com chaves com aspeto real | Apenas valores fictícios |
| Erros de rede devolviam uma resposta com estado HTTP 460 inventado | `ConnectionException` |
| Erros 4xx/5xx devolvidos como resposta normal | `ApiException` |
| Endpoints e métodos HTTP alteráveis por `.env` | Fixos no código |
| Shortcode `171717` por omissão | Obrigatório |
| Valores arredondados silenciosamente | Recusados se tiverem mais de 2 casas decimais |
