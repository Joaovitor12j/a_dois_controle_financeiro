# 0011. Vale/benefício como extensão de forma de pagamento, com renda mensal gerada na criação

## Contexto

Surgiu a necessidade de cadastrar meios de recebimento com valor mensal
fixo — vale-alimentação, vale-transporte, auxílio home office — que vêm de
uma conta (ex.: a conta de um provedor de benefícios como a Swile), têm um
limite mensal e um dia de recebimento, mas, diferente de cartão de crédito,
não geram fatura.

Esse valor mensal também precisa aparecer como renda, para entrar nos
cálculos de renda do usuário, sem exigir um cadastro duplicado (uma vez na
forma de pagamento, outra vez manualmente em rendas).

## Decisão

Vale e benefício são dois tipos novos de `formas_pagamento.tipo`, cada um
com a mesma extensão 1:1 (tabela `vales_beneficio`, PK = FK
`forma_pagamento_id`), no mesmo padrão já estabelecido pela
[ADR 0009](0009-cartao-de-credito-como-extensao-de-forma-de-pagamento.md)
para cartão de crédito: `limite` (valor mensal disponibilizado) e
`dia_recebimento`. Por herdar a regra geral de tipo imutável, corrigir um
vale/benefício cadastrado errado exige excluir e recriar, não editar o
tipo.

Ao criar uma forma de pagamento do tipo vale ou benefício, o sistema cria,
na mesma transação, uma `Renda` de recorrência mensal vinculada à mesma
conta, usando o `limite` como valor e o `dia_recebimento` informado. A
categoria de renda e a data de início são informadas pelo usuário no
mesmo formulário, reaproveitando a entidade `categorias_renda` já
existente — não há criação de categoria nova para esse fim.

A renda gerada não fica vinculada à forma de pagamento que a originou: a
partir da criação, ela é uma renda comum, editável e excluível
independentemente. Editar `limite`/`dia_recebimento` na forma de pagamento
depois de criada não sincroniza a renda já gerada.

## Consequências

- `formas_pagamento.tipo` ganha dois valores novos (`vale`, `beneficio`),
  ambos usando a extensão `vales_beneficio`.
- Criar uma forma de pagamento do tipo vale/benefício passa a exigir, além
  dos campos da extensão, uma `categoria_renda_id` e uma `data_inicio` —
  campos que só fazem sentido nesse momento, para compor a renda gerada.
- Editar uma forma de pagamento do tipo vale/benefício permite ajustar
  `limite`/`dia_recebimento`, mas não tem efeito sobre a renda já criada —
  ajustar o valor da renda em si é feito pela tela de rendas, sem
  automação.
- Excluir a forma de pagamento não exclui a renda gerada — são entidades
  independentes a partir da criação.

## Status

Aceita
