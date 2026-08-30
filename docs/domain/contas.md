# Contas

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

Uma conta é um agrupador nomeado que reúne as formas de pagamento e os cartões
de crédito de um usuário.

Uma conta não possui saldo próprio. Ela organiza os meios pelos quais o dinheiro
entra e sai, não o dinheiro em si.

## Propriedade e visibilidade

Toda conta pertence a exatamente um usuário.

**Conta é sempre individual.** Não existe conta conjunta — o contexto conjunto
do casal não passa por conta.

O usuário não lista, não consulta, não altera e não exclui conta do parceiro.

Uma conta do parceiro se comporta como inexistente, não como proibida: a
tentativa de acessá-la é indistinguível da tentativa de acessar uma conta que
nunca existiu.

Sem usuário autenticado, nenhuma conta é acessível.

## Identificação

Uma conta é identificada por um nome, obrigatório.

Contas são sempre apresentadas em ordem alfabética por nome.

## Exclusão

A exclusão de uma conta é lógica: a conta deixa de ser visível e utilizável, mas
seu registro é preservado.

Excluir uma conta exclui logicamente, junto, todas as suas formas de pagamento e
todos os seus cartões de crédito. Um meio de pagamento não sobrevive à conta que
o contém.

A exclusão definitiva de uma conta é uma operação distinta e não segue essa
regra de arrasto lógico.

## Questões em aberto

- **Nomes duplicados.** Hoje nada impede que o mesmo usuário tenha duas contas
  com o mesmo nome. Falta decidir se o nome deve ser único por usuário.
- **Restauração.** A exclusão é lógica, mas não existe operação de desfazer. Falta
  decidir se o registro preservado serve apenas a histórico ou se haverá
  restauração de conta excluída.
- **Efeito da restauração.** Caso haja restauração, falta decidir se as formas de
  pagamento e os cartões arrastados na exclusão voltam junto com a conta ou
  permanecem excluídos.

---

Implementado em: `app/Models/Conta.php`, `app/Services/Financeiro/ContaService.php`,
`app/Policies/ContaPolicy.php`, `app/Http/Controllers/ContaController.php`,
`database/migrations/2026_08_29_000001_create_contas_table.php`.
Coberto por `tests/Feature/ContaTest.php`.
