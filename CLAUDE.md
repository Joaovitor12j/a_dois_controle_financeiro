# CLAUDE.md

Instruções de projeto para trabalhar neste repositório.

## Contexto

Migração de controle-total (Next.js + Supabase + Drizzle) para Laravel + Breeze + Inertia + React. Banco novo, sem ETL — só os 2 usuários fixos são seedados. O Next.js antigo não é referência de código a seguir; `regras-de-negocio.md` é a fonte de verdade do domínio.

## Stack e ambiente

* Laravel 12, PHP 8.3, Postgres 17
* Docker custom (Dockerfile + docker-compose), sem Sail
* Vite roda local (fora do container) para HMR
* Portas: app 8083, Postgres 5487, Vite 5183
* Testes: Pest, banco de teste Postgres real (`.env.testing`) — nunca SQLite. Garantias do domínio dependem de FK composta, CHECK constraints e enums nativos do Postgres.

## Arquitetura e exploração do repositório

O repositório possui decisões arquiteturais e regras de negócio já estabelecidas. Não fazer exploração indiscriminada do código para reconstruir uma arquitetura que já está documentada.

### Ordem de exploração

Ao analisar uma tarefa, seguir preferencialmente esta ordem:

1. Consultar este `CLAUDE.md`.
2. Consultar `regras-de-negocio.md` quando a tarefa envolver comportamento ou regra de domínio.
3. Usar **Codebase Memory** para obter visão arquitetural, módulos, dependências e relações entre componentes.
4. Usar **Serena** para navegação semântica, símbolos, referências e impacto de alterações.
5. Ler arquivos-fonte somente quando for necessário conhecer detalhes da implementação.
6. Usar **Context7** para documentação atualizada e específica de versão de frameworks, bibliotecas ou APIs.

### Evitar exploração desnecessária

* Não fazer `Glob`, `Grep` ou leitura recursiva de todo o projeto para descobrir sua estrutura.
* Não ler arquivos não relacionados à tarefa apenas para obter contexto.
* Não abrir dezenas de arquivos quando Codebase Memory ou Serena puderem identificar primeiro os componentes relevantes.
* Antes de usar `Read`, `Grep` ou `Glob` extensivamente, restringir o escopo da investigação.
* Não reconstruir manualmente relações entre classes, métodos e módulos quando Serena ou Codebase Memory puderem fornecer essa informação.
* Ler a implementação real somente depois de identificar os símbolos e arquivos relevantes.
* Não assumir que todo o repositório precisa ser conhecido antes de iniciar uma análise.
* Exploração completa do repositório somente quando explicitamente solicitada ou quando tecnicamente necessária.

### Uso dos MCPs

* **Codebase Memory**: usar para arquitetura, módulos, dependências, relações e visão macro do projeto.
* **Serena**: usar para símbolos, referências, chamadas, navegação semântica e análise de impacto/refatoração.
* **Context7**: usar para documentação oficial e comportamento específico de versões de Laravel, PHP, React, Inertia, Vite, Postgres ou outras dependências.
* Não utilizar MCPs apenas por utilizá-los: escolher a ferramenta adequada para cada tipo de informação.
* Quando uma ferramenta MCP puder responder à pergunta sem leitura dos arquivos, preferir a ferramenta.

## Planejamento

Ao criar um plano para uma alteração:

1. Entender o objetivo e as regras de negócio envolvidas.
2. Consultar `regras-de-negocio.md` quando aplicável.
3. Consultar Codebase Memory para localizar a área arquitetural afetada.
4. Usar Serena para localizar símbolos, referências e dependências relevantes.
5. Ler somente os arquivos necessários para validar a implementação atual.
6. Identificar testes existentes relacionados ao comportamento.
7. Consultar Context7 quando houver dúvida sobre comportamento de framework, biblioteca ou API.
8. Definir os arquivos que provavelmente serão alterados.
9. Identificar impactos arquiteturais, de banco e de testes.
10. Produzir o plano somente depois dessa investigação direcionada.

O plano deve ser baseado no código e na arquitetura existentes, não em uma suposição de como o projeto deveria ser estruturado.

Não criar planos baseados em uma varredura completa do repositório quando a área afetada puder ser identificada por Codebase Memory e Serena.

## Decisões de arquitetura fechadas

Não reabrir sem justificativa nova:

* **Auth = tabela de domínio**: `Usuario extends Authenticatable`, `$table = 'usuarios'`. Sem self-signup — registro, forgot-password e verificação de e-mail removidos do Breeze.
* **Visibilidade via Eloquent, não RLS**: Global Scopes (`VisivelParaUsuarioScope`, `DonoScope`) + Policies. Sem RLS no Postgres.
* **UUID via Eloquent** (`HasUuids`), não `DEFAULT gen_random_uuid()` no banco.
* **`saldo_individual` é ledger append-only** — nunca um valor mutável. Saldo atual é sempre a soma dos lançamentos.
* **Fechamento mensal / acerto / sobra**: removido do domínio atual. Não recriar essas entidades ou cálculos sem uma decisão explícita nova — a implementação anterior estava incorreta e será redesenhada do zero.

## Padrões de código

* Clean Code, SOLID, DDD no contexto Laravel: Services em `app/Services/Financeiro/`, Models finos, Controllers finos delegando a Services.
* **Value Objects** para conceitos de domínio que não são primitivos crus — principalmente dinheiro (`Money`, nunca float/int solto representando valor monetário) e outros conceitos com regra própria (ex: percentuais de rateio, período/mês de referência). VO é imutável, valida invariantes no construtor, mora em `app/Domain/ValueObjects/` (ou namespace equivalente por domínio).
* PHP 8.3+: usar recursos modernos (enums nativos com métodos, readonly properties, match).
* Sem comentários explicativos no código, salvo quando fundamentais para a lógica.
* Sem try/catch, salvo quando fundamental para a lógica do problema.
* Ao propor código, mostrar só o trecho relevante — não regenerar o arquivo inteiro, a menos que pedido.

## Testes

* Pest, banco de teste Postgres real (nunca SQLite/in-memory) — ver seção Stack e ambiente.
* Feature test = HTTP + banco; Unit test = função/Value Object puro, sem framework.
* Value Objects testados isoladamente (validação de invariantes, imutabilidade).

## Verificação antes de concluir uma tarefa

Rodar localmente ao final de qualquer tarefa, antes de considerar concluída (não é pipeline de CI, é checagem manual/local):

```bash
composer check
```

Que roda, em sequência: Pint (formatação), PHPStan/Larastan (análise estática), Pest (testes). Todos precisam passar antes de dar a tarefa por concluída — falha em qualquer um dos três significa a tarefa não está pronta.

Em tarefas de frontend, `composer check` não cobre TypeScript. Rodar também:

```bash
npm run build
```

Que roda `tsc` (type-check, `strict: true`) e depois o build do Vite.

**Não fazer teste manual no navegador.** Nada de abrir a aplicação, logar e clicar na tela para validar — a verificação é `composer check` e `npm run build`. A conferência visual é do usuário.

## Pontos de atenção conhecidos

* `RecorrenciaMaterializador` precisa de `withoutGlobalScope(...)` explícito para processar despesas/rendas dos dois usuários — bypassar o scope errado quebra o isolamento entre usuários.
* FK composta `despesas(categoria_id, tipo) → categorias(id, tipo)`: despesa conjunta nunca pode referenciar categoria individual.
* Despesa recorrente: `data` só muda dentro do mesmo mês (preserva "uma cópia por origem por mês" da materialização). Despesa avulsa muda livremente.


## Fonte de verdade do domínio

`regras-de-negocio.md` é a fonte de verdade das regras de negócio atualmente definidas.

Regras ainda não documentadas não devem ser inferidas a partir da implementação legada ou de decisões anteriores.

Quando uma regra existente estiver sendo redesenhada, a nova decisão explícita do usuário prevalece sobre o código legado e sobre regras anteriores.
