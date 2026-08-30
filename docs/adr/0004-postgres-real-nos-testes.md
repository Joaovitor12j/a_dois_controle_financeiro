# 0004 — Testes contra Postgres real, nunca SQLite

## Contexto

SQLite em memória é a escolha convencional por velocidade. Mas garantias
centrais do domínio moram no banco: CHECK constraints, índices únicos parciais,
tipos ENUM nativos e chaves estrangeiras compostas.

Sob SQLite essas garantias ou não existem ou se comportam de outra forma. Um
teste verde em SQLite não diz nada sobre o comportamento em produção justamente
onde o domínio é mais rígido.

## Decisão

A suíte roda contra um Postgres real, configurado em `.env.testing`. SQLite e
bancos em memória não são usados em nenhuma circunstância.

## Consequências

- A suíte exige um Postgres disponível; não roda em ambiente sem banco.
- Os testes são mais lentos que o equivalente em memória — custo aceito em troca
  de testar as garantias reais.
- Violação de constraint aparece como falha de teste, e não como comportamento
  silenciosamente diferente.

## Status

Aceita.
