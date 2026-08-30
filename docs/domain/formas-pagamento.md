# Formas de pagamento

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui. Regras de propriedade e exclusão de conta estão em
[contas.md](contas.md) e também não são repetidas aqui.

## Conceito

Uma forma de pagamento é um meio pelo qual o dinheiro entra ou sai de uma
conta: débito, dinheiro ou pix.

Toda forma de pagamento pertence a exatamente uma conta e, por herança, ao
usuário dono dessa conta. Não existe forma de pagamento sem conta.

## Propriedade e visibilidade

A posse de uma forma de pagamento é sempre a posse da conta a que ela
pertence. O usuário não cria, altera ou exclui forma de pagamento vinculada a
uma conta que não é sua.

Uma forma de pagamento vinculada a uma conta do parceiro se comporta como
inexistente, não como proibida — mesmo princípio aplicado a conta em
[contas.md](contas.md).

## Identificação

Uma forma de pagamento é identificada por um nome, obrigatório, e um tipo,
obrigatório, dentre débito, dinheiro ou pix.

## Saldo inicial

Uma forma de pagamento pode ser criada com um saldo inicial, em centavos.
Quando informado, o saldo inicial é registrado como uma movimentação
marcada como saldo inicial, na data informada.

O saldo inicial é imutável: não existe operação de edição de saldo inicial
depois que a forma de pagamento é criada.

## Exclusão

A exclusão de uma forma de pagamento é lógica, seguindo a mesma regra geral
de exclusão lógica do domínio.

## Questões em aberto

- **Cascata para movimentações.** Excluir uma forma de pagamento hoje não
  arrasta logicamente as movimentações associadas a ela. Falta decidir se
  deveria haver essa cascata, análoga à de conta → forma de pagamento.
- **Edição de tipo com movimentações existentes.** Hoje é permitido editar o
  tipo de uma forma de pagamento mesmo havendo movimentações associadas a
  ela. Falta decidir se isso deveria ser restrito.

---

Implementado em: `app/Models/FormaPagamento.php`,
`app/Services/Financeiro/FormaPagamentoService.php`,
`app/Policies/FormaPagamentoPolicy.php`,
`app/Http/Controllers/FormaPagamentoController.php`,
`database/migrations/2026_08_29_000002_create_formas_pagamento_table.php`.
