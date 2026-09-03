# Movimentações

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

Uma movimentação representa um evento real de dinheiro entrando ou saindo,
associado a uma forma de pagamento.

Este é o recorte do domínio de movimentação redesenhado até agora — cobre o
pagamento de despesa e o recebimento de renda. Fatura e saldo inicial
continuam não redesenhados; nenhuma regra deve ser inferida daqui para eles.

## Sinal do valor

Saída de dinheiro é registrada com valor negativo — caso do pagamento de
despesa. Entrada de dinheiro é registrada com valor positivo — caso do
recebimento de renda.

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

- **Relação com fatura.** Ainda não redesenhada.
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
`app/Http/Requests/DesfazerRecebimentoRendaRequest.php`.
