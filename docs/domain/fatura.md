# Fatura

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

Fatura é uma entidade própria (não é `Despesa`, não tem `tipo_lancamento`) —
ver [ADR 0018](../adr/0018-fatura-volta-a-ser-entidade-propria.md). Ela é o
agregado, por cartão de crédito e competência de vencimento, das despesas
pagas naquele cartão dentro de uma janela de fechamento.

Fatura não nasce de um formulário livre: ela é **gerada**, manualmente, a
partir de um cartão de crédito e uma competência de vencimento. A tela de
Despesas não lista fatura — ela tem gerenciamento próprio numa aba dedicada,
exatamente por não ser um lançamento de mão livre. Fatura não tem
`categoria_despesa_id`: não é um gasto próprio, é o agregado de outros gastos
que já têm categoria individualmente.

## Campos

Fatura tem `cartao_credito_id` obrigatório — o cartão de crédito ao qual
pertence —, `competencia` (mês de vencimento, sempre dia 1) e
`data_vencimento`, calculada na geração. Existe no máximo uma fatura por par
cartão + competência.

`valor` não é digitado: é sempre o resultado do cálculo de geração ou do
recálculo automático, descritos abaixo.

## Geração

Gerar uma fatura pede um cartão de crédito (forma de pagamento do tipo
crédito) e uma competência de vencimento. A competência de uma fatura é o
mês do **vencimento** do cartão (`dia_vencimento`), não do fechamento —
mesmo cartão, mesma competência de vencimento, produz sempre a mesma fatura.

### Regra de fechamento

Uma movimentação com forma de pagamento do tipo crédito pertence ao ciclo de
fechamento corrente do cartão se sua data for até o `dia_fechamento`;
depois do fechamento, pertence ao ciclo seguinte. O ciclo de fechamento se
traduz na competência de vencimento comparando `dia_vencimento` com
`dia_fechamento`: quando o dia de vencimento é menor ou igual ao dia de
fechamento, o vencimento cai no mês seguinte ao do fechamento; quando é
maior, cai no mesmo mês do fechamento. Essa regra vale para qualquer
despesa paga em crédito, não só parcelada.

### O que a fatura agrega

Gerar a fatura de um cartão numa competência de vencimento soma:

- **parcelas de despesa parcelada** daquele cartão, cuja movimentação de
  pagamento existe **na competência do ciclo de fechamento** correspondente
  (a mesma competência que `CalculadoraCompetenciaDespesa` já usa para
  numerar a parcela — ver [despesas.md](despesas.md#natureza-do-lançamento)).
  Parcela não tem data de pagamento própria: a data que a ação de "marcar
  como paga" registra é só quando o usuário clicou, não quando a parcela
  "aconteceu" — por isso a fatura agrupa por competência, nunca pela data
  bruta da movimentação. Uma parcela paga com a data do clique depois do
  dia de fechamento continua entrando na fatura certa, porque o que decide
  é a competência da parcela, já fixada desde a compra.
- **despesas única ou mensal** pagas com aquele cartão como forma de
  pagamento, cuja movimentação caiu dentro da janela de fechamento (data
  da movimentação entre o fechamento anterior, exclusive, e o fechamento
  desta competência, inclusive). Única e mensal não têm uma competência de
  ciclo própria como a parcelada — o dado que existe é só a data em que
  foram pagas, por isso aqui a regra é mesmo pela data.

Não existe vínculo persistido entre essas movimentações e a fatura — a
agregação é sempre recalculada a partir do cartão e da competência pedida,
sem precisar de tabela de ligação. Gerar fatura para uma janela sem nenhuma
movimentação em crédito é rejeitado — não existe fatura de valor zero.

## Regeneração

Enquanto não paga, gerar a fatura de um cartão + competência que já tem
fatura apenas recalcula o valor sobre a mesma fatura (mesmo id, mesma
`data_vencimento`) — não cria uma segunda. Regenerar uma fatura já paga é
bloqueado.

### Recálculo automático

Fatura ainda não paga não fica com valor parado esperando uma ação manual:
toda vez que a tela de Faturas é aberta, cada fatura não paga tem o valor
recalculado automaticamente contra os itens que ela agrega naquele momento —
sem precisar clicar em "Gerar fatura" de novo. Se o recálculo automático
zerar o total (as despesas que compunham a fatura deixaram de existir ou de
estar na janela), a fatura é **excluída automaticamente** — mesma regra de
"não existe fatura de valor zero", aplicada também fora da geração explícita.
Fatura já paga nunca é recalculada, automática ou manualmente.

## Pagamento

Pagamento de fatura tem mecanismo próprio, não reaproveita o pagamento de
despesa: uma movimentação para cada item da fatura que ainda não tinha
movimentação própria (marcando parcela/única/mensal como paga), mais uma
movimentação agregada no valor total da fatura, na forma de pagamento real
escolhida no momento do pagamento (nunca o próprio cartão). Essa movimentação
agregada desconta saldo normalmente, porque sua forma de pagamento não é
crédito — ver [movimentacoes.md](movimentacoes.md).

## Fatura e os totais do período

Fatura nunca entra no total de despesas do período, em "despesa por
categoria" nem em "despesa por forma de pagamento" — ver
[dashboard.md](dashboard.md). Quem conta nesses totais é a despesa original
(a parcela, a única, a mensal), esteja ela coberta por uma fatura já gerada
ou não. Contar os dois seria duplicar o mesmo gasto.

## Questões em aberto

- **Imutabilidade pós-pagamento.** Hoje regenerar uma fatura já paga é
  bloqueado, mas não há regra sobre o que fazer quando uma nova movimentação
  em crédito aparece na janela de uma fatura já paga (ex.: parcela marcada
  como paga depois, com data retroativa à janela). Falta decidir se isso
  deveria gerar algum alerta ou permanecer simplesmente fora do valor já
  fechado.
- **Exclusão de fatura gerada por engano.** O recálculo automático já
  resolve o caso de a fatura zerar sozinha (ela é excluída automaticamente),
  mas cancelar manualmente uma fatura ainda não paga, gerada por engano
  enquanto ainda tem itens, não foi pensado como fluxo dedicado.

---

Implementado em:
`database/migrations/2026_09_08_000005_remover_fatura_de_despesas.php`,
`database/migrations/2026_09_08_000006_recriar_faturas_e_fatura_id_em_movimentacoes.php`,
`app/Models/Fatura.php`, `app/Domain/Financeiro/CalculadoraFatura.php`,
`app/Domain/Financeiro/CalculadoraCompetenciaDespesa.php`,
`app/Services/Financeiro/FaturaService.php`, `app/Http/Controllers/FaturaController.php`,
`app/Http/Requests/GerarFaturaRequest.php`, `resources/js/Pages/Faturas/Index.tsx`.
