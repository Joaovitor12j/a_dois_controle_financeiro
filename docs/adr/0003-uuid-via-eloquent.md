# 0003 — UUID gerado pela aplicação, não pelo banco

## Contexto

As chaves primárias são UUID. A geração pode ficar no banco
(`DEFAULT gen_random_uuid()`) ou na aplicação.

## Decisão

O UUID é gerado pelo Eloquent, via trait `HasUuids`. As colunas são declaradas
como `uuid('id')->primary()`, sem default no banco.

## Consequências

- O identificador existe antes do insert, o que permite montar grafos de objetos
  relacionados sem round-trip ao banco.
- Inserts feitos fora do Eloquent — SQL cru, fixtures — precisam fornecer o id.
- A garantia de formato depende da aplicação, não do banco.

## Status

Aceita.
