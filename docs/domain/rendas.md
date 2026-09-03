# Rendas

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui. Regras de propriedade e exclusão de conta estão em
[contas.md](contas.md) e também não são repetidas aqui.

## Conceito

Uma renda representa uma entrada financeira do usuário, vinculada a uma conta
e a uma categoria de renda.

Toda renda pertence a exatamente um usuário e a exatamente uma conta. Não
existe renda sem conta.

## Propriedade e visibilidade

A posse de uma renda é direta: o usuário dono da renda é quem a criou. O
usuário não cria, altera ou exclui renda que não é sua.

Uma renda de outro usuário se comporta como inexistente, não como proibida —
mesmo princípio aplicado a conta em [contas.md](contas.md).

## Identificação e valor

Uma renda é identificada por uma descrição, obrigatória.

Uma renda tem um valor, obrigatório, em centavos, e sempre maior que zero.

## Categoria

Toda renda pertence a uma categoria de renda, obrigatória. A categoria de
renda é uma entidade compartilhada entre os usuários, não um dado por conta
ou por usuário.

## Recorrência

Uma renda tem um tipo de recorrência: única ou mensal.

Renda de recorrência **única** tem uma data de recebimento, obrigatória, e
não tem dia de recebimento, data de início nem data de fim — esses campos
são proibidos para esse tipo.

Renda de recorrência **mensal** tem um dia de recebimento (entre 1 e 31) e
uma data de início, ambos obrigatórios, e não tem data de recebimento — esse
campo é proibido para esse tipo. A data de fim é opcional e, quando
informada, não pode ser anterior à data de início.

## Recebimento

Recebimento não é um atributo da renda. É representado por uma
**movimentação** vinculada à renda por competência (mês) — ver
[movimentacoes.md](movimentacoes.md#recebimento-de-renda) e
[ADR 0014](../adr/0014-renda-usa-forma-de-pagamento-designada-da-conta.md). Uma
renda nunca guarda status, data ou forma de pagamento em si; esses dados vivem
só na movimentação correspondente.

Renda **única** tem uma única competência possível, derivada da própria
renda. Renda **mensal** tem uma competência por mês do intervalo entre
`data_inicio` e `data_fim` (ou em aberto, se `data_fim` não estiver definida)
— mesmo mecanismo já usado para despesa mensal.

Uma renda está recebida numa competência quando existe uma movimentação com
esse `renda_id` e essa `competencia` — nunca mais que uma por combinação (ver
índice único em [movimentacoes.md](movimentacoes.md)).

O valor recebido é informado no momento do recebimento, pré-preenchido com o
valor programado da renda mas editável — pode divergir do programado (ex.:
salário com desconto diferente do previsto, freela pago a menos). O valor da
renda em si não muda; só o valor da movimentação daquela competência reflete
o que foi efetivamente recebido.

A forma de pagamento usada no recebimento não é escolhida no momento do
evento — é derivada da(s) forma(s) de pagamento da conta da renda marcadas
como `recebe_renda` (ver [formas-pagamento.md](formas-pagamento.md)):
automática se existir só uma elegível, perguntada ao usuário se existir mais
de uma, recusada se não existir nenhuma.

Encerrar uma renda mensal (definir ou antecipar `data_fim`) é bloqueado
quando a nova data cai antes de uma competência já recebida — mesma regra já
existente para despesa mensal. Essa validação é de aplicação, não de banco.

## Questões em aberto

- **Categoria de renda.** Ainda não tem documento de domínio próprio; suas
  regras (quem pode criar/editar categorias, se é compartilhada entre os dois
  usuários) não estão definidas.

---

Implementado em: `app/Models/Renda.php`,
`app/Models/Movimentacao.php`,
`app/Models/FormaPagamento.php`,
`app/Services/Financeiro/RendaService.php`,
`app/Policies/RendaPolicy.php`,
`app/Http/Controllers/RendaController.php`,
`app/Http/Requests/MarcarComoRecebidaRendaRequest.php`,
`app/Http/Requests/DesfazerRecebimentoRendaRequest.php`,
`app/Domain/Financeiro/CalculadoraOcorrenciaRenda.php`,
`resources/js/Pages/Rendas/Index.tsx`,
`resources/js/Pages/Rendas/Partials/ItemOcorrenciaRenda.tsx`,
`resources/js/Pages/Rendas/Partials/MarcarComoRecebidaRenda.tsx`,
`resources/js/Pages/Rendas/Partials/ConfirmarDesfazerRecebimento.tsx`,
`database/migrations/2026_08_31_000002_create_rendas_table.php`,
`database/migrations/2026_08_31_000003_add_foreign_key_renda_id_to_movimentacoes_table.php`,
`database/migrations/2026_09_02_000004_add_recebe_renda_to_formas_pagamento_table.php`,
`database/migrations/2026_09_02_000005_generalize_movimentacoes_competencia_check_para_renda.php`.
