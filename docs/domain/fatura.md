# Fatura

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

Fatura é o quarto tipo de lançamento de despesa, ao lado de única, mensal e
parcelada — ver [despesas.md](despesas.md#natureza-do-lançamento). Não é uma
entidade própria: uma fatura é uma despesa como qualquer outra, com
`tipo_lancamento` igual a `fatura`, sujeita ao mesmo mecanismo de pagamento
via movimentação (competência, `marcar como paga`, `desfazer pagamento`) —
ver [ADR 0016](../adr/0016-fatura-como-quarto-tipo-de-despesa.md) e
[ADR 0012](../adr/0012-pagamento-de-despesa-como-movimentacao.md).

Diferente dos outros três tipos, fatura não nasce de um formulário livre: ela
é **gerada**, manualmente, a partir de um cartão de crédito e uma
competência de vencimento. A tela de Despesas não lista fatura — ela tem
gerenciamento próprio numa aba dedicada, exatamente por não ser um
lançamento de mão livre.

## Campos

Fatura tem `forma_pagamento_id` obrigatório — o cartão de crédito ao qual
pertence — e `data_vencimento` obrigatória, calculada na geração. Não tem
`categoria_despesa_id`: é o único tipo de despesa isento da obrigatoriedade
de categoria (regra geral em [despesas.md](despesas.md#categoria)), porque
não é um gasto próprio, é o agregado de outros gastos que já têm categoria
individualmente. Não tem `dia_vencimento`, `data_inicio`, `data_fim`,
`numero_parcelas` nem `data_primeira_parcela` — proibidos, mesma regra de
despesa única.

`valor` não é digitado: é sempre o resultado do cálculo de geração,
descrito abaixo.

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
fatura apenas recalcula o valor sobre a mesma despesa (mesmo id, mesma
`data_vencimento`) — não cria uma segunda. Regenerar uma fatura já paga é
bloqueado.

## Pagamento

Pagamento de fatura não tem mecanismo próprio: segue exatamente o mesmo
caminho de pagamento de despesa única — uma movimentação, valor total,
forma de pagamento real escolhida no momento do pagamento (nunca o próprio
cartão). Essa movimentação desconta saldo normalmente, porque sua forma de
pagamento não é crédito — ver [movimentacoes.md](movimentacoes.md).

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
- **Exclusão de fatura gerada por engano.** Uma fatura segue as mesmas
  regras gerais de exclusão de despesa (não há exceção documentada), mas o
  efeito de excluir uma fatura ainda não paga e depois regenerá-la não foi
  pensado como fluxo dedicado.

---

Implementado em:
`database/migrations/2026_09_08_000002_add_fatura_to_tipo_lancamento_despesa_enum.php`,
`database/migrations/2026_09_08_000003_add_fatura_ao_despesas_campos_por_tipo_check.php`,
`database/migrations/2026_09_08_000004_drop_faturas_e_fatura_id_de_movimentacoes.php`,
`app/Enums/TipoLancamentoDespesa.php`, `app/Domain/Financeiro/CalculadoraFatura.php`,
`app/Domain/Financeiro/CalculadoraCompetenciaDespesa.php`,
`app/Services/Financeiro/DespesaService.php`, `app/Http/Controllers/FaturaController.php`,
`app/Http/Requests/GerarFaturaRequest.php`, `resources/js/Pages/Faturas/Index.tsx`.
