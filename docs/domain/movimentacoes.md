# Movimentações

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

Uma movimentação representa um evento real de dinheiro entrando ou saindo,
associado a uma forma de pagamento.

Este é o primeiro recorte do domínio de movimentação a ser redesenhado — cobre
apenas o necessário para o pagamento de despesa. Renda, fatura e saldo inicial
continuam não redesenhados; nenhuma regra deve ser inferida daqui para eles.

## Sinal do valor

Saída de dinheiro é registrada com valor negativo. Entrada será positiva,
quando o lado de renda for redesenhado.

## Pagamento de despesa

Uma movimentação com `despesa_id` preenchido representa o pagamento de uma
ocorrência de despesa numa competência (mês) específica — ver
[despesas.md](despesas.md#pagamento) e
[ADR 0012](../adr/0012-pagamento-de-despesa-como-movimentacao.md).

Não existe mais que uma movimentação por despesa por competência.

Quem pagou não é campo próprio da movimentação — deriva de
`forma_pagamento → conta → usuario`.

## Questões em aberto

- **Geração de movimentação a partir de renda.** Mesma lacuna, mesmo mecanismo
  a ser reaproveitado quando o domínio de renda for redesenhado.
- **Relação com fatura.** Ainda não redesenhada.
- **Saldo inicial.** Legado, fora do recorte deste documento.

---

Implementado em: `app/Models/Movimentacao.php`,
`database/migrations/2026_08_29_000005_create_movimentacoes_table.php`,
`database/migrations/2026_08_31_000003_add_foreign_key_renda_id_to_movimentacoes_table.php`,
`database/migrations/2026_08_31_000008_add_foreign_key_despesa_id_to_movimentacoes_table.php`,
`database/migrations/2026_09_02_000003_add_competencia_to_movimentacoes_table.php`.
