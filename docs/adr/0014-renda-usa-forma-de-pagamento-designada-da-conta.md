# 0014. Renda usa forma de pagamento designada da conta, não escolha livre no recebimento

## Contexto

Renda precisa do mesmo mecanismo já criado para despesa: recebimento como evento
imutável em `movimentacoes`, nunca como atributo mutável da renda (ver
[ADR 0012](0012-pagamento-de-despesa-como-movimentacao.md)). Mas o recebimento de
renda difere do pagamento de despesa num ponto: despesa pergunta livremente qual
forma de pagamento usar a cada pagamento (exceto parcelada, que deriva do cartão da
compra). Renda não pode seguir o mesmo padrão — o dinheiro que entra numa renda
recorrente entra sempre na(s) mesma(s) forma(s) de pagamento organizacionais da
conta, não numa escolha ad-hoc a cada recebimento.

## Decisão

`formas_pagamento` ganha a coluna `recebe_renda`, booleana, sem limite de quantas
por conta — uma conta pode ter mais de uma forma marcada (ex.: vale-alimentação e
auxílio home office na mesma conta, saldos independentes). Proibida para
`tipo = credito`: dinheiro não entra num cartão de crédito.

A forma de pagamento usada no recebimento de uma renda não é escolhida no
momento do evento: é derivada das formas de pagamento da conta da renda marcadas
como `recebe_renda` — automática quando existe só uma elegível, perguntada ao
usuário quando existe mais de uma, recusada quando não existe nenhuma.

O restante do mecanismo espelha despesa: uma movimentação com `renda_id`
preenchido representa o recebimento de uma ocorrência de renda numa competência
específica; não existe mais que uma movimentação por renda por competência
(`movimentacoes.renda_id` + `movimentacoes.competencia`); quem recebeu deriva de
`forma_pagamento → conta → usuario`. A mesma regra de bloqueio de encerramento
retroativo (`data_fim` antes de competência já recebida) já existente para
despesa mensal se aplica aqui.

## Consequências

- O mecanismo de resolução de forma de pagamento no recebimento fica assimétrico
  ao de despesa: despesa sempre pergunta (exceto parcelada, que sempre deriva);
  renda deriva por padrão e só pergunta em caso de ambiguidade real.
- `movimentacoes_competencia_despesa_check` e o índice único que hoje só cobrem
  `despesa_id` são generalizados para também cobrir `renda_id` — mesma coluna
  `competencia`, mesmo formato (`Competencia` / `CompetenciaCast`).
- Service, Controller e frontend de recebimento de renda ficam fora do escopo
  desta decisão, que é só de modelagem — reaproveitam este mecanismo numa tarefa
  seguinte.

## Status

Aceita
