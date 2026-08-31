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
| Cartões de crédito | [cartoes-credito.md](cartoes-credito.md) |
| Rendas | [rendas.md](rendas.md) |

## Estado do redesenho

O domínio está sendo redesenhado do zero, um domínio por vez. Somente os
domínios listados na tabela acima possuem regras definidas.

Para todo o resto:

- a existência de uma tabela, migration, model ou tela **não** constitui regra de
  negócio;
- regras do domínio anterior não são válidas por terem sido implementadas;
- nenhuma regra deve ser inferida da implementação legada — enquanto o domínio
  não for redesenhado e documentado, ele não tem regra definida.

## Questões em aberto

- **Como o contexto conjunto se materializa financeiramente.** Está definido que
  conta é sempre individual (ver [contas.md](contas.md)). Ainda não está definido
  por onde uma movimentação conjunta é efetivamente paga: se fora do conceito de
  conta, se por rateio sobre as contas individuais, ou por outro mecanismo.
