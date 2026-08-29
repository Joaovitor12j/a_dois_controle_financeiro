# CLAUDE.md

Instruções de projeto para trabalhar neste repositório.

## Contexto

Migração de controle-total (Next.js + Supabase + Drizzle) para Laravel + Breeze + Inertia + React. Banco novo, sem ETL — só os 2 usuários fixos são seedados. O Next.js antigo não é referência de código a seguir; `regras-de-negocio.md` é a fonte de verdade do domínio.

## Stack e ambiente

- Laravel 12, PHP 8.3, Postgres 17
- Docker custom (Dockerfile + docker-compose), sem Sail
- Vite roda local (fora do container) para HMR
- Portas: app 8083, Postgres 5487, Vite 5183
- Testes: Pest, banco de teste Postgres real (`.env.testing`) — nunca SQLite. Garantias do domínio dependem de FK composta, CHECK constraints e enums nativos do Postgres.

## Decisões de arquitetura fechadas

Não reabrir sem justificativa nova:

- **Auth = tabela de domínio**: `Usuario extends Authenticatable`, `$table = 'usuarios'`. Sem self-signup — registro, forgot-password e verificação de e-mail removidos do Breeze.
- **Visibilidade via Eloquent, não RLS**: Global Scopes (`VisivelParaUsuarioScope`, `DonoScope`) + Policies. Sem RLS no Postgres.
- **UUID via Eloquent** (`HasUuids`), não `DEFAULT gen_random_uuid()` no banco.
- **`saldo_individual` é ledger append-only** — nunca um valor mutável. Saldo atual é sempre a soma dos lançamentos.
- **Fechamento mensal / acerto / sobra**: removido do domínio atual. Não recriar essas entidades ou cálculos sem uma decisão explícita nova — a implementação anterior estava incorreta e será redesenhada do zero.

## Padrões de código

- Clean Code, SOLID, DDD no contexto Laravel: Services em `app/Services/Financeiro/`, Models finos, Controllers finos delegando a Services.
- **Value Objects** para conceitos de domínio que não são primitivos crus — principalmente dinheiro (`Money`, nunca float/int solto representando valor monetário) e outros conceitos com regra própria (ex: percentuais de rateio, período/mês de referência). VO é imutável, valida invariantes no construtor, mora em `app/Domain/ValueObjects/` (ou namespace equivalente por domínio).
- PHP 8.3+: usar recursos modernos (enums nativos com métodos, readonly properties, match).
- Sem comentários explicativos no código, salvo quando fundamentais para a lógica.
- Sem try/catch, salvo quando fundamental para a lógica do problema.
- Ao propor código, mostrar só o trecho relevante — não regenerar o arquivo inteiro, a menos que pedido.

## Testes

- Toda regra de negócio documentada em `regras-de-negocio.md` tem teste correspondente — sem meta numérica de cobertura, o critério é regra coberta, não porcentagem de linha.
- Pest, banco de teste Postgres real (nunca SQLite/in-memory) — ver seção Stack e ambiente.
- Feature test = HTTP + banco; Unit test = função/Value Object puro, sem framework.
- Value Objects testados isoladamente (validação de invariantes, imutabilidade).

## Verificação antes de concluir uma tarefa

Rodar localmente ao final de qualquer tarefa, antes de considerar concluída (não é pipeline de CI, é checagem manual/local):

```bash
composer check
```

Que roda, em sequência: Pint (formatação), PHPStan/Larastan (análise estática), Pest (testes). Todos precisam passar antes de dar a tarefa por concluída — falha em qualquer um dos três significa a tarefa não está pronta.

## Pontos de atenção conhecidos

- `RecorrenciaMaterializador` precisa de `withoutGlobalScope(...)` explícito para processar despesas/rendas dos dois usuários — bypassar o scope errado quebra o isolamento entre usuários.
- FK composta `despesas(categoria_id, tipo) → categorias(id, tipo)`: despesa conjunta nunca pode referenciar categoria individual.
- Despesa recorrente: `data` só muda dentro do mesmo mês (preserva "uma cópia por origem por mês" da materialização). Despesa avulsa muda livremente.
