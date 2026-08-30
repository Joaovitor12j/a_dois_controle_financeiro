# Cartões de crédito

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui. Regras de propriedade e exclusão de conta estão em
[contas.md](contas.md) e também não são repetidas aqui.

## Conceito

Um cartão de crédito é um meio de pagamento com limite e ciclo de fatura
próprios.

Todo cartão de crédito pertence a exatamente uma conta e, por herança, ao
usuário dono dessa conta. Não existe cartão de crédito sem conta.

## Propriedade e visibilidade

A posse de um cartão de crédito é sempre a posse da conta a que ele
pertence. O usuário não cria, altera ou exclui cartão de crédito vinculado a
uma conta que não é sua.

Um cartão de crédito vinculado a uma conta do parceiro se comporta como
inexistente, não como proibida — mesmo princípio aplicado a conta em
[contas.md](contas.md).

## Identificação

Um cartão de crédito é identificado por um nome, obrigatório.

## Limite

Um cartão de crédito tem um limite total, obrigatório, em centavos.

Um cartão de crédito pode ser criado com um limite já usado na abertura, em
centavos, para representar o limite comprometido no momento em que o cartão
passa a ser controlado pelo sistema. Quando não informado, é zero.

O limite usado na abertura é imutável: não existe operação de edição desse
valor depois que o cartão é criado. Diferente do saldo inicial de forma de
pagamento, ele é um campo próprio do cartão e não gera movimentação.

## Ciclo de fatura

Um cartão de crédito tem um dia de fechamento e um dia de vencimento,
ambos obrigatórios e entre 1 e 31.

## Exclusão

A exclusão de um cartão de crédito é lógica, seguindo a mesma regra geral
de exclusão lógica do domínio.

## Questões em aberto

- **Fatura.** O relacionamento entre cartão de crédito e fatura ainda não
  foi redesenhado — este documento cobre apenas o cartão em si.
- **Consistência do dia de vencimento com o de fechamento.** Hoje nada
  impede que o dia de vencimento seja anterior ao dia de fechamento. Falta
  decidir se essa combinação deve ser validada.
- **Edição do limite usado na abertura por correção.** O valor é imutável
  por regra, mas falta decidir se deve haver uma via de correção para erro
  de cadastro (distinta de uma edição normal).

---

Implementado em: `app/Models/CartaoCredito.php`,
`app/Services/Financeiro/CartaoCreditoService.php`,
`app/Policies/CartaoCreditoPolicy.php`,
`app/Http/Controllers/CartaoCreditoController.php`,
`database/migrations/2026_08_29_000003_create_cartoes_credito_table.php`.
