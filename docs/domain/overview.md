# Visão geral do domínio

Conceitos gerais e compartilhados do controle financeiro do casal.

Este documento contém apenas o que vale para todo o domínio. Regras específicas
pertencem ao documento do respectivo domínio e não são repetidas aqui.

## Conceito

A aplicação é um sistema de controle financeiro destinado a casais.

Existem dois usuários. Não há cadastro aberto: os usuários são fixos.

## Contextos de movimentação

O modelo financeiro possui dois contextos de movimentação.

### Movimentação individual

Cada usuário possui sua própria movimentação financeira.

Movimentações individuais pertencem exclusivamente ao usuário que as criou.

Um usuário não deve visualizar, consultar ou interferir nas movimentações
individuais do parceiro.

### Movimentação conjunta

O casal possui uma movimentação financeira compartilhada.

Movimentações conjuntas pertencem ao contexto do casal e são visíveis para
ambos os usuários.

## Princípio de separação

A aplicação deve manter claramente separados:

- dados financeiros individuais de cada usuário;
- dados financeiros compartilhados pelo casal.

Uma operação individual não deve ser tratada como conjunta e uma operação
conjunta não deve ser tratada como individual.

## Domínios

| Domínio | Documento |
| --- | --- |
| Contas | [contas.md](contas.md) |
| Formas de pagamento | [formas-pagamento.md](formas-pagamento.md) |
| Rendas | [rendas.md](rendas.md) |
| Despesas | [despesas.md](despesas.md) |
| Movimentações | [movimentacoes.md](movimentacoes.md) |
| Dashboard | [dashboard.md](dashboard.md) |

## Estado do redesenho

O domínio está sendo redesenhado do zero, um domínio por vez. Somente os
domínios listados na tabela acima possuem regras definidas.

Para todo o resto:

- a existência de uma tabela, migration, model ou tela **não** constitui regra de
  negócio;
- regras do domínio anterior não são válidas por terem sido implementadas;
- nenhuma regra deve ser inferida da implementação legada — enquanto o domínio
  não for redesenhado e documentado, ele não tem regra definida.

## Como o contexto conjunto se materializa financeiramente

Conta é sempre individual (ver [contas.md](contas.md)) — o contexto conjunto
não passa por conta nem por rateio entre contas individuais.

Despesa foi o primeiro domínio a resolver isso: carrega um campo `contexto`
(`individual`/`conjunta`) que determina sua visibilidade — ver
[despesas.md](despesas.md) e [ADR 0010](../adr/0010-visibilidade-de-despesa-contexto-individual-conjunta.md).
Outros domínios que vierem a precisar de contexto conjunto seguem o mesmo
mecanismo.

## Questões em aberto

Nenhuma no momento além das registradas em cada documento de domínio.
