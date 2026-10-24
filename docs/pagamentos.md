# Pagamentos

## Referências

| Referência | Quem gera | Uso |
|---|---|---|
| `reference` (`input_TransactionReference`) | A aplicação | Descrição/identificador visível da transação. |
| `thirdPartyReference` (`input_ThirdPartyReference`) | A aplicação | Identificador único da aplicação. Repetido = `INS-10`. |
| `transactionId` (`output_TransactionID`) | M-Pesa | Identificador da transação na M-Pesa. Usado em `reversal()` e `query()`. |
| `conversationId` (`output_ConversationID`) | M-Pesa | Identificador do pedido na M-Pesa. Pode ser usado em `query()`. |

Guarde sempre a `thirdPartyReference` **antes** de chamar a API e os identificadores M-Pesa depois.

## Estados possíveis de um C2B

| Resultado da chamada | Significado | O que fazer |
|---|---|---|
| `TransactionResponse` (`INS-0`) | A M-Pesa reporta sucesso. | Marcar como pago; guardar `transactionId`. |
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
$estado = $mpesa->query($transactionId, 'Q'.Str::random(10));

if ($estado->isCompleted()) {
    // output_ResponseTransactionStatus === "Completed"
}
```

- `queryReference`: `transactionId` ou `conversationId` da transação original.
- `thirdPartyReference`: referência desta consulta.
- `isCompleted()` só devolve `true` para `"Completed"`. Qualquer outro valor (ou ausência do campo) devolve `false`; estados desconhecidos nunca contam como pagos.
- O valor original fica em `$estado->transactionStatus` para a aplicação decidir.

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

```php
$mpesa->reversal(amount: 250, transactionId: $transactionId, thirdPartyReference: 'REV'.$id);
```

Exige `MPESA_INITIATOR_IDENTIFIER` e `MPESA_SECURITY_CREDENTIAL`. O valor é enviado como `input_Amount`, como no SDK original; confirme no portal se a sua conta espera outro nome de campo.
