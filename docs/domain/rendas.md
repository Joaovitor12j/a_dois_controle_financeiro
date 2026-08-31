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

## Questões em aberto

- **Geração de movimentações a partir de renda recorrente.** Este documento
  cobre apenas o cadastro da renda — a forma como uma renda mensal gera
  movimentações ao longo do tempo ainda não foi redesenhada.
- **Categoria de renda.** Ainda não tem documento de domínio próprio; suas
  regras (quem pode criar/editar categorias, se é compartilhada entre os dois
  usuários) não estão definidas.

---

Implementado em: `app/Models/Renda.php`,
`app/Services/Financeiro/RendaService.php`,
`app/Policies/RendaPolicy.php`,
`app/Http/Controllers/RendaController.php`,
`database/migrations/2026_08_31_000002_create_rendas_table.php`.
