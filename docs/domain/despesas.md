# Despesas

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

Uma despesa representa uma saída financeira, vinculada a um usuário e a uma
categoria de despesa.

## Propriedade e visibilidade

Toda despesa carrega um contexto: **individual** ou **conjunta** — ver
[ADR 0010](../adr/0010-visibilidade-de-despesa-contexto-individual-conjunta.md).

Despesa **individual** é visível, editável e excluível apenas pelo usuário que
a criou. Uma despesa individual de outro usuário se comporta como
inexistente, não como proibida — mesmo princípio aplicado a conta em
[contas.md](contas.md).

Despesa **conjunta** é visível, editável e excluível pelos dois usuários,
independentemente de quem a criou.

## Identificação e valor

Uma despesa é identificada por uma descrição, obrigatória.

Uma despesa tem um valor, obrigatório, em centavos, e sempre maior que zero.
Para despesa parcelada, o valor representa a parcela, não o total — o total
é sempre `valor × número de parcelas`, calculado em runtime.

## Categoria

Toda despesa pertence a uma categoria de despesa, obrigatória. Regras da
categoria em si (propriedade, identificação, aparência, exclusão) estão em
[categorias.md](categorias.md).

## Natureza do lançamento

Uma despesa tem um tipo de lançamento: **única**, **mensal** ou
**parcelada**.

Despesa **única** tem uma data de vencimento, obrigatória, e não tem dia de
vencimento, data de início, data de fim, número de parcelas nem data da
primeira parcela — esses campos são proibidos para esse tipo.

Despesa **mensal** tem um dia de vencimento (entre 1 e 31) e uma data de
início, ambos obrigatórios. A data de início representa o mês de início do
lançamento, não um dia específico — é sempre registrada como o primeiro dia
do mês, mesmo que a intenção seja apenas identificar mês e ano. A data de fim
é opcional e, quando informada, não pode ser anterior à data de início. Não
tem data de vencimento, forma de pagamento, número de parcelas nem data da
primeira parcela — esses campos são proibidos para esse tipo.

Despesa **parcelada** tem uma forma de pagamento, um número de parcelas
(maior que zero) e uma data da primeira parcela, todos obrigatórios. Não tem
data de vencimento, dia de vencimento, data de início nem data de fim — esses
campos são proibidos para esse tipo. A forma de pagamento de uma despesa
parcelada deve ser do tipo crédito — essa regra cruza com forma de pagamento
e não é garantida no banco, é validação de aplicação.

## Pagamento

Pagamento não é um atributo da despesa. É representado por uma
**movimentação** vinculada à despesa por competência (mês) — ver
[movimentacoes.md](movimentacoes.md) e
[ADR 0012](../adr/0012-pagamento-de-despesa-como-movimentacao.md). Uma
despesa nunca guarda status, data ou forma de pagamento em si; esses dados
vivem só na movimentação correspondente.

Cada tipo de lançamento mapeia numa ou várias competências possíveis:

- Despesa **única** tem uma única competência possível, derivada da própria
  despesa.
- Despesa **mensal** tem uma competência por mês do intervalo entre
  `data_inicio` e `data_fim` (ou em aberto, se `data_fim` não estiver
  definida).
- Despesa **parcelada** tem uma competência por parcela, a partir de
  `data_primeira_parcela`.

Uma despesa está paga numa competência quando existe uma movimentação com
esse `despesa_id` e essa `competencia` — nunca mais que uma por combinação
(ver índice único em [movimentacoes.md](movimentacoes.md)).

`forma_pagamento_id` deixa de existir em despesa única e despesa mensal.
Continua existindo, exclusivamente, em despesa **parcelada** — mas ali não é
mais dado de pagamento: é o cartão da compra, atributo genuíno da despesa,
presente desde a criação e independente de qualquer parcela estar paga.

Encerrar uma despesa mensal (definir ou antecipar `data_fim`) é bloqueado
quando a nova data cai antes de uma competência já paga — não é possível
encerrar retroativamente um período que já teve pagamento registrado. Essa
validação é de aplicação, não de banco.

Despesa **única** pode ser criada já paga: a tela de criação aceita marcar a
despesa como paga informando data e forma de pagamento com os demais
dados. Isso não é um atributo persistido na despesa — ao criar, a aplicação
grava a despesa e, se marcada como paga, cria na mesma operação a movimentação
de pagamento na única competência possível da despesa (a da própria
`data_vencimento`), pelo mesmo mecanismo de qualquer outra marcação de
pagamento. Mensal e parcelada não têm essa opção na criação — só passam a ter
competências pagas após criadas, pela ação dedicada de marcar como paga.

## Questões em aberto

- **Relação entre parcelamento e fatura.** Uma despesa parcelada paga em
  cartão de crédito presumivelmente se relaciona com fatura, mas fatura
  ainda não foi redesenhada neste ciclo.

---

Implementado em:
`database/migrations/2026_08_31_000006_create_categorias_despesa_table.php`,
`database/migrations/2026_08_31_000007_create_despesas_table.php`,
`database/migrations/2026_08_31_000008_add_foreign_key_despesa_id_to_movimentacoes_table.php`,
`database/migrations/2026_08_31_000009_fix_despesas_pagamento_check_constraint.php`,
`database/migrations/2026_09_02_000001_add_despesas_data_inicio_primeiro_dia_check.php`,
`database/migrations/2026_09_02_000002_remove_pagamento_from_despesas_table.php`,
`database/migrations/2026_09_02_000003_add_competencia_to_movimentacoes_table.php`,
`app/Models/Despesa.php`, `app/Models/Movimentacao.php`,
`app/Services/Financeiro/DespesaService.php`,
`app/Policies/DespesaPolicy.php`, `app/Http/Controllers/DespesaController.php`.
