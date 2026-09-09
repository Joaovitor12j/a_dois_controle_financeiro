# Assistente

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

O assistente é uma conversa em que o usuário pergunta em texto livre sobre a
própria situação financeira e recebe uma resposta com dados já existentes no
sistema. Não introduz dado financeiro próprio: tudo o que responde deriva de
renda, despesa e movimentação — ver [rendas.md](rendas.md),
[despesas.md](despesas.md) e [movimentacoes.md](movimentacoes.md).

A interpretação da mensagem é determinística: o sistema reconhece um
conjunto fechado de perguntas e um vocabulário controlado. O assistente não é
um interpretador de linguagem aberta e não deve ser descrito como tal — ver
[ADR 0019](../adr/0019-interpretacao-deterministica-da-mensagem-do-assistente.md).

## Apresentação

O assistente não é uma tela do sistema e não tem lugar próprio na navegação.

Ele é um recurso permanentemente disponível: um acionador flutuante presente
em qualquer tela autenticada, que abre a conversa sobreposta ao conteúdo
atual sem tirar o usuário de onde ele está, e que pode ser fechado voltando
à mesma tela no mesmo estado.

Consequência disso para as regras: a conversa é contínua e independente da
tela em que foi aberta. O assistente não recebe contexto da tela atual — o
que o usuário estava vendo não altera a interpretação da pergunta nem os
filtros aplicados. Período, categoria e contexto vêm exclusivamente da
mensagem, conforme as regras abaixo.

## Perguntas reconhecidas

O MVP reconhece cinco perguntas. Cada uma corresponde a uma consulta que já
existe no domínio:

1. Total de despesas de um período, com filtro opcional por categoria e por
   contexto.
2. Listagem de despesas de um período, com os mesmos filtros opcionais.
3. Pendências do período — despesas ainda não pagas, conforme a definição já
   dada em [dashboard.md](dashboard.md#pendências).
4. Total de rendas de um período.
5. Resumo do período — renda total, despesa total e resultado.

Pergunta fora dessa lista não é respondida. Regra nova de pergunta
reconhecida exige atualizar este documento.

## Período

O período de uma pergunta é sempre um mês de competência, nunca um intervalo
aberto.

- Mês não informado na mensagem: mês corrente.
- Mês informado sem ano: ano corrente.
- Mês e ano informados: o que foi informado.

## Visibilidade

O assistente obedece exatamente a mesma visibilidade do resto da aplicação,
sem exceção própria:

- Despesa individual do parceiro nunca aparece, em nenhuma pergunta, em
  nenhum agregado.
- Despesa de contexto conjunta aparece para ambos, conforme
  [ADR 0010](../adr/0010-visibilidade-de-despesa-contexto-individual-conjunta.md).
- Renda do parceiro está fora do escopo do MVP. Não existe pergunta
  agregando renda dos dois usuários, justamente para não exigir a exceção de
  leitura da [ADR 0013](../adr/0013-agregacao-casal-no-dashboard-bypass-pontual-donoscope.md).

## Formato da resposta

Quando o resultado tem valor em contexto individual e em contexto conjunto,
a resposta apresenta os dois discriminados. Nunca colapsa os dois num único
número sem identificar de onde vem cada parte.

Quando um dos contextos não tem valor no período, ele é omitido em vez de
aparecer como zero.

## Pergunta não reconhecida

Mensagem que o sistema não consegue interpretar recebe uma resposta
explícita de não compreensão, indicando o que ele sabe responder.

"Não entendi a pergunta" e "não há dados no período" são respostas
distintas e nunca devem ser confundidas: ausência de resultado é informação
válida, falha de interpretação não é.

O assistente nunca responde com valor aproximado, estimado ou parcial
quando a interpretação falhou.

## Histórico

A conversa persiste. Cada turno guarda a pergunta do usuário, a
interpretação que o sistema derivou dela e a resposta devolvida.

O histórico pertence ao usuário que conversou e é visível apenas a ele,
mesmo quando a pergunta tratou de dado conjunto.

O registro da interpretação existe para dois fins: auditoria quando a
escrita entrar no escopo, e medição de quais perguntas o sistema deixou de
reconhecer.

## Fronteira de leitura

Nenhuma pergunta do assistente altera dado. Ele não cria, edita nem exclui
renda, despesa ou movimentação.

Quando a escrita entrar no escopo, ela não será execução direta: o
assistente proporá a operação e a execução dependerá de confirmação
explícita do usuário. Isso é direção decidida, não regra vigente — não há
hoje nenhuma regra de criação de movimentação ou despesa pelo assistente.

## Questões em aberto

- **Sinônimo de categoria fora do nome cadastrado.** Se haverá apelidos por
  categoria ou outro mecanismo de correspondência.
- **Referência ao turno anterior**, do tipo "e no mês passado?". Se a
  interpretação herda filtros da mensagem anterior.
- **Pergunta composta**, cobrindo mais de uma categoria ou mais de um
  período numa só mensagem.
- **Renda do casal e modo Casal no assistente**, que dependeriam da exceção
  de leitura da [ADR 0013](../adr/0013-agregacao-casal-no-dashboard-bypass-pontual-donoscope.md).
- **Despesa parcelada e fatura**, mesma lacuna já registrada em
  [despesas.md](despesas.md) e [dashboard.md](dashboard.md).
