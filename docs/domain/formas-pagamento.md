# Formas de pagamento

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui. Regras de propriedade e exclusão de conta estão em
[contas.md](contas.md) e também não são repetidas aqui.

## Conceito

Uma forma de pagamento é um meio pelo qual o dinheiro entra ou sai de uma
conta: débito, dinheiro, pix, crédito, vale ou benefício.

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
obrigatório, dentre débito, dinheiro, pix, crédito, vale ou benefício.

O tipo é imutável: não existe operação de edição de tipo depois que a forma
de pagamento é criada. Corrigir um tipo cadastrado errado exige excluir a
forma de pagamento e recriar.

## Saldo inicial

Uma forma de pagamento pode ser criada com um saldo inicial, em centavos.
Quando informado, o saldo inicial é registrado como uma movimentação
marcada como saldo inicial, na data informada.

O saldo inicial é imutável: não existe operação de edição de saldo inicial
depois que a forma de pagamento é criada.

Saldo inicial não se aplica a forma de pagamento do tipo crédito — é
proibido informá-lo nesse caso.

## Crédito

Uma forma de pagamento do tipo crédito tem limite e ciclo de fatura
próprios, obrigatórios apenas para esse tipo.

### Limite

Uma forma de pagamento do tipo crédito tem um limite total, obrigatório, em
centavos.

Ela pode ser criada com um limite já usado na abertura, em centavos, para
representar o limite comprometido no momento em que passa a ser controlada
pelo sistema. Quando não informado, é zero.

O limite usado na abertura é imutável: não existe operação de edição desse
valor depois que a forma de pagamento é criada. Diferente do saldo inicial,
ele é um campo próprio do crédito e não gera movimentação.

### Ciclo de fatura

Uma forma de pagamento do tipo crédito tem um dia de fechamento e um dia de
vencimento, ambos obrigatórios e entre 1 e 31.

## Vale e benefício

Vale e benefício são dois tipos de forma de pagamento que representam um
meio de recebimento como vale-alimentação, vale-transporte ou auxílio home
office. Estruturalmente, são idênticos a débito, dinheiro e pix: nome, tipo
e saldo inicial opcional — sem dados próprios adicionais. A diferença para
os demais tipos é só o rótulo usado para classificar esse meio, para que o
usuário identifique esse tipo de recebimento separadamente na conta.

## Exclusão

A exclusão de uma forma de pagamento é lógica, seguindo a mesma regra geral
de exclusão lógica do domínio.

Para o tipo crédito, a exclusão lógica da forma de pagamento é suficiente:
os dados de limite e ciclo de fatura não têm exclusão lógica própria — sua
visibilidade segue inteiramente a da forma de pagamento a que pertencem.

## Questões em aberto

- **Cascata para movimentações.** Excluir uma forma de pagamento hoje não
  arrasta logicamente as movimentações associadas a ela. Falta decidir se
  deveria haver essa cascata, análoga à de conta → forma de pagamento.
- **Fatura.** O relacionamento entre crédito e fatura ainda não foi
  redesenhado — este documento cobre apenas a forma de pagamento em si.
- **Consistência do dia de vencimento com o de fechamento (crédito).** Hoje
  nada impede que o dia de vencimento seja anterior ao dia de fechamento.
  Falta decidir se essa combinação deve ser validada.
- **Edição do limite usado na abertura por correção (crédito).** O valor é
  imutável por regra, mas falta decidir se deve haver uma via de correção
  para erro de cadastro (distinta de uma edição normal).

---

Implementado em: `app/Models/FormaPagamento.php`, `app/Models/CartaoCredito.php`,
`app/Services/Financeiro/FormaPagamentoService.php`,
`app/Policies/FormaPagamentoPolicy.php`,
`app/Http/Controllers/FormaPagamentoController.php`,
`database/migrations/2026_08_29_000002_create_formas_pagamento_table.php`,
`database/migrations/2026_08_31_000005_recriar_cartoes_credito_como_extensao_forma_pagamento.php`,
`database/migrations/2026_09_01_000001_add_vale_beneficio_to_tipo_forma_pagamento_enum.php`.
