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

Toda despesa pertence a uma categoria de despesa, obrigatória. A categoria de
despesa é uma entidade compartilhada entre os usuários, não um dado por conta
ou por usuário — mesmo modelo de categoria de renda.

## Natureza do lançamento

Uma despesa tem um tipo de lançamento: **única**, **mensal** ou
**parcelada**.

Despesa **única** tem uma data de vencimento, obrigatória, e não tem dia de
vencimento, data de início, data de fim, número de parcelas nem data da
primeira parcela — esses campos são proibidos para esse tipo.

Despesa **mensal** tem um dia de vencimento (entre 1 e 31) e uma data de
início, ambos obrigatórios. A data de fim é opcional e, quando informada, não
pode ser anterior à data de início. Não tem data de vencimento, forma de
pagamento, número de parcelas nem data da primeira parcela — esses campos são
proibidos para esse tipo. Despesa mensal não é paga diretamente: não se
aplica o conceito de "paga" à despesa em si (ver Pagamento, abaixo).

Despesa **parcelada** tem uma forma de pagamento, um número de parcelas
(maior que zero) e uma data da primeira parcela, todos obrigatórios. Não tem
data de vencimento, dia de vencimento, data de início nem data de fim — esses
campos são proibidos para esse tipo. A forma de pagamento de uma despesa
parcelada deve ser do tipo crédito — essa regra cruza com forma de pagamento
e não é garantida no banco, é validação de aplicação.

## Pagamento

O conceito de "paga" só se aplica a despesa **única**. Uma despesa única
nasce não paga. Ao ser paga, recebe uma data de pagamento e uma forma de
pagamento, ambas obrigatórias nesse momento.

Enquanto não paga, a data de pagamento não é preenchida, mas a forma de
pagamento pode ser informada com antecedência (ex.: já se sabe como a
despesa será paga antes do pagamento efetivo) — só não é obrigatória.

Despesa mensal e despesa parcelada não têm o conceito de "paga" no cadastro
da despesa em si — o pagamento de cada ocorrência/parcela é uma questão em
aberto (ver abaixo).

## Questões em aberto

- **Geração de ocorrências de despesa mensal.** Este documento cobre apenas o
  cadastro da despesa — a forma como uma despesa mensal gera ocorrências ao
  longo do tempo ainda não foi redesenhada. É a mesma lacuna já aberta em
  [rendas.md](rendas.md) para renda mensal (geração de movimentações a partir
  de recorrência); o mecanismo, quando definido, deve valer para os dois
  domínios, não ser resolvido separadamente.
- **Marcação de pagamento por ocorrência/parcela.** Decorre da questão
  anterior: sem ocorrência definida, não há onde registrar o pagamento de uma
  parcela de despesa parcelada ou de um mês específico de despesa mensal.
- **Relação entre parcelamento e fatura.** Uma despesa parcelada paga em
  cartão de crédito presumivelmente se relaciona com fatura, mas fatura
  ainda não foi redesenhada neste ciclo.
- **Categoria de despesa.** Ainda não tem documento de domínio próprio; suas
  regras (quem pode criar/editar categorias, se é compartilhada entre os dois
  usuários) não estão definidas — mesma lacuna aberta para categoria de
  renda.

---

Implementado em:
`database/migrations/2026_08_31_000006_create_categorias_despesa_table.php`,
`database/migrations/2026_08_31_000007_create_despesas_table.php`,
`database/migrations/2026_08_31_000008_add_foreign_key_despesa_id_to_movimentacoes_table.php`,
`database/migrations/2026_08_31_000009_fix_despesas_pagamento_check_constraint.php`.
