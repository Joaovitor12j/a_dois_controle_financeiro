# 0017. Generalização: crédito nunca desconta saldo real, nem na evolução de saldo do dashboard

## Contexto

`FormaPagamento::saldo()` já retornava `null` para forma de pagamento do
tipo crédito, e crédito já era proibido de ter saldo inicial (ver
[formas-pagamento.md](../domain/formas-pagamento.md#saldo-inicial)). Ou
seja, uma movimentação com forma de pagamento em crédito nunca somava no
saldo de nenhuma forma de pagamento — essa parte já era verdadeira por
construção, sem precisar de correção de código.

A evolução diária de saldo do dashboard (`DashboardService::resumirPeriodo`),
porém, não seguia essa mesma regra: ao encontrar a movimentação de
pagamento de uma despesa (qualquer tipo, exceto parcelada, que já era
totalmente excluída), ela entrava direto no bucket "realizado", sem olhar o
tipo da forma de pagamento usada. Uma despesa única ou mensal paga em
cartão de crédito aparecia como saída de saldo já realizada no dia do
"pagamento" — o que não é dinheiro saindo de conta nenhuma, é uma dívida
sendo assumida no cartão. O dinheiro só sai de fato quando a fatura em si é
paga (ver [ADR 0016](0016-fatura-como-quarto-tipo-de-despesa.md)).

## Decisão

Generaliza-se a regra "crédito nunca desconta saldo real" para cobrir
explicitamente a evolução diária de saldo do dashboard: ao processar a
movimentação de pagamento de uma despesa (qualquer tipo), se a forma de
pagamento dessa movimentação for do tipo crédito, o evento não entra nem
como realizado nem como projetado — ele simplesmente não existe na série de
saldo, porque o evento financeiro real (a saída de dinheiro) ainda não
aconteceu.

O evento equivalente passa a ser o pagamento da fatura: como fatura é uma
despesa como outra qualquer, e sua movimentação de pagamento usa a forma de
pagamento real escolhida no momento (nunca o cartão), ela entra
normalmente no bucket "realizado" pelo mecanismo já existente — sem
precisar de nenhum código dedicado a fatura no cálculo de saldo.

## Consequências

- `DashboardService::resumirPeriodo` passa a pular o evento (nem certo, nem
  projetado) quando a movimentação de pagamento de uma despesa usa forma de
  pagamento do tipo crédito.
- O total "Despesa" do resumo do período continua contando a despesa paga
  em crédito normalmente — só a evolução diária de saldo muda. A distinção
  entre "quanto foi gasto" e "quanto efetivamente saiu de alguma conta" fica
  mais explícita.
- Regra documentada explicitamente em [movimentacoes.md](../domain/movimentacoes.md#crédito-nunca-desconta-saldo-real)
  e em [dashboard.md](../domain/dashboard.md), mesmo a parte que já era
  verdadeira sem mudança de código — para não ficar implícita só na
  implementação de `FormaPagamento::saldo()`.

## Status

Aceita
