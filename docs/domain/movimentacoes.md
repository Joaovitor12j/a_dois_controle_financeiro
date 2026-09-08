# Movimentações

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

Uma movimentação representa um evento real de dinheiro entrando ou saindo,
associado a uma forma de pagamento.

Este é o recorte do domínio de movimentação redesenhado até agora — cobre o
pagamento de despesa (incluindo fatura, que é um tipo de despesa — ver
[fatura.md](fatura.md)) e o recebimento de renda. Saldo inicial continua não
redesenhado; nenhuma regra deve ser inferida daqui para ele.

## Sinal do valor

Saída de dinheiro é registrada com valor negativo — caso do pagamento de
despesa. Entrada de dinheiro é registrada com valor positivo — caso do
recebimento de renda.

## Crédito nunca desconta saldo real

Nenhuma movimentação cuja forma de pagamento é do tipo crédito desconta
saldo real de nenhuma conta — vale para pagamento de parcela de despesa
parcelada e para despesa única ou mensal paga em crédito, sem distinção.
Consequência direta de crédito nunca ter saldo (nem saldo inicial, nem soma
de movimentações — ver [formas-pagamento.md](formas-pagamento.md#saldo)):
crédito representa dívida potencial, não dinheiro disponível, então uma
movimentação com forma de pagamento em crédito nunca é "dinheiro saindo de
uma conta" — quem efetivamente descontou saldo é o pagamento da fatura
correspondente (ver [fatura.md](fatura.md#pagamento)), não o lançamento
original. Essa mesma regra vale para a evolução diária de saldo do
dashboard — ver [dashboard.md](dashboard.md).

## Pagamento de despesa

Uma movimentação com `despesa_id` preenchido representa o pagamento de uma
ocorrência de despesa numa competência (mês) específica — ver
[despesas.md](despesas.md#pagamento) e
[ADR 0012](../adr/0012-pagamento-de-despesa-como-movimentacao.md).

Não existe mais que uma movimentação por despesa por competência.

Quem pagou não é campo próprio da movimentação — deriva de
`forma_pagamento → conta → usuario`.

## Recebimento de renda

Uma movimentação com `renda_id` preenchido representa o recebimento de uma
ocorrência de renda numa competência (mês) específica — ver
[rendas.md](rendas.md#recebimento) e
[ADR 0014](../adr/0014-renda-usa-forma-de-pagamento-designada-da-conta.md).

Não existe mais que uma movimentação por renda por competência.

Quem recebeu não é campo próprio da movimentação — deriva de
`forma_pagamento → conta → usuario`, mesmo princípio já usado para pagamento
de despesa.

## Questões em aberto

- **Saldo inicial.** Legado, fora do recorte deste documento.

---

Implementado em: `app/Models/Movimentacao.php`,
`database/migrations/2026_08_29_000005_create_movimentacoes_table.php`,
`database/migrations/2026_08_31_000003_add_foreign_key_renda_id_to_movimentacoes_table.php`,
`database/migrations/2026_08_31_000008_add_foreign_key_despesa_id_to_movimentacoes_table.php`,
`database/migrations/2026_09_02_000003_add_competencia_to_movimentacoes_table.php`,
`database/migrations/2026_09_02_000005_generalize_movimentacoes_competencia_check_para_renda.php`,
`app/Services/Financeiro/RendaService.php`, `app/Http/Controllers/RendaController.php`,
`app/Http/Requests/MarcarComoRecebidaRendaRequest.php`,
`app/Http/Requests/DesfazerRecebimentoRendaRequest.php`,
`database/migrations/2026_09_08_000004_drop_faturas_e_fatura_id_de_movimentacoes.php`.
