# Pagamentos

## Referências

| Referência | Quem gera | Uso |
|---|---|---|
| `reference` (`input_TransactionReference`) | A aplicação | Descrição/identificador visível da transação. Entre 1 e 20 caracteres (`INS-17`). |
| `thirdPartyReference` (`input_ThirdPartyReference`) | A aplicação | Identificador único da aplicação. Repetido = `INS-10`. |
| `transactionId` (`output_TransactionID`) | M-Pesa | Identificador da transação na M-Pesa. Usado em `reversal()` e `query()`. |
| `conversationId` (`output_ConversationID`) | M-Pesa | Identificador do pedido na M-Pesa. Usado em `query()` e para ligar o callback assíncrono (`input_OriginalConversationID`). |

Guarde sempre a `thirdPartyReference` **antes** de chamar a API e os identificadores M-Pesa depois.

## Estados possíveis de um C2B

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

## Iniciar não é confirmar

Uma resposta HTTP bem-sucedida, por si só, não prova o pagamento. O SDK exige **as duas** condições: HTTP 2xx e `output_ResponseCode = INS-0`. Mesmo assim, recomenda-se reconciliar periodicamente com `query()`, sobretudo para pedidos que ficaram em estado desconhecido.

## Consultar o estado

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

## Modo assíncrono

Se a conta estiver em modo assíncrono, a resposta imediata só contém `output_ConversationID`, `output_ThirdPartyReference`, `output_ResponseCode` e `output_ResponseDesc`. `INS-0` significa apenas "pedido aceite". `TransactionResponse::isAwaitingResult()` devolve `true` nesse caso.

O resultado final chega ao Response URL (ver README, secção 11) com `input_ResultCode = "0"` em caso de sucesso, e pode ser confirmado com `query($conversationId, ...)`.

## Repetições e duplicados

- O SDK **não repete** pedidos automaticamente.
- A M-Pesa recusa uma `thirdPartyReference` repetida com `INS-10` (`ApiException::isDuplicate()`). Se reutilizar a mesma referência ao repetir um pagamento em estado desconhecido, a M-Pesa impede a cobrança dupla; mas um `INS-10` não diz se a primeira tentativa foi concluída — confirme com `query()`.
- O SDK não inventa um mecanismo de idempotência próprio.

## Valores

- Aceita `int`, `float` ou `string` numérica, maior que zero, com até 2 casas decimais.
- Valores com mais casas são **recusados**, não arredondados (o SDK original arredondava).
- Enviado como texto: `10` → `"10"`, `10.50` → `"10.5"`.

## Números de telefone

Aceita números Vodacom (84 e 85) nos formatos `84XXXXXXX`, `25884XXXXXXX`, `+25884XXXXXXX`, `0025884XXXXXXX`, com espaços ou hífens. Enviado como `25884XXXXXXX`.

## Reversões

Reverte uma transação **bem-sucedida**: a M-Pesa retira o valor da carteira de quem recebeu e devolve-o a quem iniciou a transação original.

```php
// Valor indicado: reversão parcial ou total desse montante (input_ReversalAmount)
$mpesa->reversal(amount: 250, transactionId: $transactionId, thirdPartyReference: 'REV'.$id);

// Sem valor: reversão total
$mpesa->reversal(null, $transactionId, 'REV'.$id);
```

Exige `MPESA_INITIATOR_IDENTIFIER` e `MPESA_SECURITY_CREDENTIAL`. Credenciais erradas devolvem `INS-24`, `INS-25` ou `INS-2001`.
