# 0007 — Fechamento mensal, acerto e sobra removidos do domínio

## Contexto

A implementação anterior (Next.js) possuía entidades e cálculos de fechamento
mensal, acerto entre o casal e sobra. Esses cálculos estavam incorretos e o
modelo por trás deles não se sustentava.

## Decisão

Fechamento mensal, acerto e sobra são removidos do domínio. Não existem como
entidade, cálculo ou tela.

Não devem ser recriados a partir da implementação anterior. Se o problema que
resolviam voltar à pauta, será redesenhado do zero, a partir de uma decisão nova
e explícita, registrada em nova ADR e nas regras do domínio correspondente.

## Consequências

- A ausência dessas entidades é deliberada, não uma lacuna a preencher.
- Encontrar referência a elas no código legado não é justificativa para
  reintroduzi-las.

## Status

Aceita.
