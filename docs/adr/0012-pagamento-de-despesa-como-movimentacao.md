# 0012. Pagamento de despesa como movimentação, não como atributo

## Contexto

`despesas` chegou a acumular `paga`, `data_pagamento` e `forma_pagamento_id` como
colunas próprias, duplicando o que deveria ser um evento do razão financeiro
(`movimentacoes`). Isso tratava pagamento como um estado mutável da despesa em vez
de um evento imutável e datado, e não escalava para despesa mensal e parcelada, que
têm várias ocorrências de pagamento possíveis — uma por competência/parcela — não
uma só.

## Decisão

Status de pagamento nunca é armazenado na despesa — é sempre derivado da existência
de uma `Movimentacao` para uma competência específica (`movimentacoes.despesa_id` +
`movimentacoes.competencia`). O mecanismo é único para os três tipos de lançamento:
despesa única tem uma única competência possível; mensal e parcelada têm várias,
calculadas em runtime, nunca persistidas antes do pagamento real.

`despesas.paga` e `despesas.data_pagamento` são removidas. `forma_pagamento_id`
deixa de existir em despesa única e mensal — passa a existir só em despesa
parcelada, onde não representa mais dado de pagamento, e sim o cartão da compra
(atributo genuíno, presente desde a criação, independente de qualquer parcela estar
paga).

`movimentacoes` ganha a coluna `competencia` (mesmo formato de
`faturas.competencia` — sempre dia 1, VO `Competencia` via `CompetenciaCast`), e um
índice único em `(despesa_id, competencia)` garante uma movimentação por despesa
por competência.

## Consequências

- Primeira escrita real em `movimentacoes` sob o domínio novo.
- Renda tem a mesma lacuna de geração de movimentação — resolvida quando o domínio
  de renda for redesenhado, reaproveitando este mecanismo.
- `Fatura` fica de fora desta decisão por enquanto.
- `DespesaService::marcarComoPaga`/`desfazerPagamento`, os `FormRequest`s e o
  controller que hoje leem/escrevem `paga`/`data_pagamento` ficam quebrados até
  serem reescritos sobre `movimentacoes` numa tarefa seguinte — fora do escopo desta
  decisão, que é só de modelagem.

## Status

Aceita
