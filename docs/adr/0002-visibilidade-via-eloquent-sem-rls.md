# 0002 — Visibilidade via Eloquent, não via RLS

## Contexto

O domínio exige isolamento entre os dados individuais dos dois usuários. Postgres
oferece Row Level Security, que garante isolamento no nível do banco.

RLS, porém, exige propagar a identidade do usuário até a sessão de banco, o que
atravessa o pool de conexões, complica migrations, seeds e testes, e torna o
comportamento difícil de observar a partir do código da aplicação.

## Decisão

O isolamento é implementado na camada Eloquent, com dois mecanismos
complementares:

- **Global Scopes** filtram o que a query enxerga. `DonoScope` restringe a
  consulta ao usuário autenticado e, sem usuário autenticado, devolve conjunto
  vazio.
- **Policies** autorizam a operação sobre o registro já carregado.

Não há RLS no Postgres.

## Consequências

- Um registro de outro usuário se comporta como inexistente (404), não como
  proibido (403), porque o scope o remove antes de a autorização ser avaliada.
- Processamento fora do ciclo de request — jobs, comandos, seeds — não tem
  usuário autenticado e precisa remover o scope explicitamente com
  `withoutGlobalScope(DonoScope::class)`. Remover o scope errado quebra o
  isolamento; é o ponto mais frágil desta decisão.
- O isolamento vale para o código da aplicação. Acesso direto ao banco não é
  protegido.

## Status

Aceita.
