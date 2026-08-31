# 0009. Cartão de crédito como extensão de forma de pagamento

## Contexto

`cartoes_credito` existia como entidade irmã de `formas_pagamento`, ambas
filhas de `conta`. Essa modelagem deixava `movimentacoes.forma_pagamento_id`
(`NOT NULL`) sem como ser preenchido quando o pagamento era feito por cartão
de crédito — não havia um único campo capaz de representar "como foi pago"
para os dois casos.

Além disso, o domínio de formas de pagamento e o de cartões de crédito
duplicavam regras de propriedade, visibilidade e exclusão lógica por
herdarem, cada um separadamente, da mesma conta.

## Decisão

Cartão de crédito deixa de ser entidade independente e passa a ser uma
extensão 1:1 de forma de pagamento: a tabela `cartoes_credito` usa
`forma_pagamento_id` como chave primária e estrangeira (PK = FK), e
`formas_pagamento.tipo` ganha o valor `credito`.

Toda referência a "como foi pago" (movimentações, e futuramente qualquer
outro lançamento) aponta exclusivamente para `forma_pagamento_id`, sem
exceção para cartão.

A extensão não tem exclusão lógica própria — sua visibilidade segue
inteiramente o soft delete da forma de pagamento a que pertence.

Como consequência direta dessa unificação, `tipo` de forma de pagamento
passa a ser imutável após a criação. Isso resolve as questões em aberto que
existiam sobre editar o tipo com movimentações associadas e sobre transição
de/para cartão de crédito — ambas deixam de fazer sentido quando o tipo não
pode mudar.

## Consequências

- `movimentacoes.forma_pagamento_id` passa a cobrir os dois casos sem campo
  alternativo, permitindo o domínio de movimentação avançar.
- Regras de propriedade, visibilidade e exclusão lógica de cartão de crédito
  deixam de ser duplicadas: são as mesmas de forma de pagamento.
- Criar ou editar uma forma de pagamento do tipo `credito` passa a exigir os
  campos de cartão (`limite_total`, `dia_fechamento`, `dia_vencimento`) na
  mesma operação, e a proibir campos que não fazem sentido para crédito
  (`saldo_inicial`).
- `tipo` imutável significa que corrigir o tipo de uma forma de pagamento
  cadastrada errada exige excluir e recriar, não editar.
- `faturas.cartao_credito_id` passa a referenciar
  `cartoes_credito.forma_pagamento_id` em vez de um `id` próprio — a fatura em
  si não foi redesenhada nesta decisão.

## Status

Aceita
