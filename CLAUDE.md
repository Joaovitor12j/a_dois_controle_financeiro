# CLAUDE.md

Instruções de projeto para trabalhar neste repositório.

## Contexto

Migração de controle-total (Next.js + Supabase + Drizzle) para Laravel + Breeze + Inertia + React. Banco novo, sem ETL — só os 2 usuários fixos são seedados. O Next.js antigo não é referência de código a seguir.

O domínio está sendo redesenhado do zero, um domínio por vez. `docs/domain/` é a fonte de verdade das regras de negócio.

## Stack e ambiente

* Laravel 12, PHP 8.3, Postgres 17
* Docker custom (Dockerfile + docker-compose), sem Sail
* Vite roda local (fora do container) para HMR
* Portas: app 8083, Postgres 5487, Vite 5183
* Testes: Pest, banco de teste Postgres real (`.env.testing`) — nunca SQLite. Garantias do domínio dependem de FK composta, CHECK constraints e enums nativos do Postgres.

## Documentação do projeto

* `CLAUDE.md` (este arquivo): como trabalhar no repositório — stack, padrões, verificação.
* [`docs/domain/overview.md`](docs/domain/overview.md): conceitos gerais e compartilhados do domínio.
* `docs/domain/<dominio>.md`: regras de um domínio específico (ex.: [`contas.md`](docs/domain/contas.md)).
* [`docs/adr/`](docs/adr/README.md): decisões arquiteturais, uma por arquivo.

A documentação representa intenção e regra de negócio. O código representa a implementação atual. Quando os dois divergirem, a documentação decide o que é correto.

## Arquitetura e exploração do repositório

O repositório possui decisões arquiteturais e regras de negócio já estabelecidas. Não fazer exploração indiscriminada do código para reconstruir uma arquitetura que já está documentada.

### Ordem de exploração

Ao analisar uma tarefa, seguir preferencialmente esta ordem:

1. Consultar este `CLAUDE.md`.
2. Consultar `docs/domain/overview.md` para o contexto geral do domínio.
3. Consultar o documento do domínio envolvido na tarefa (`docs/domain/<dominio>.md`).
4. Consultar `docs/adr/` quando a tarefa tocar uma decisão arquitetural.
5. Usar **Codebase Memory** para obter visão arquitetural, módulos, dependências e relações entre componentes.
6. Usar **Serena** para navegação semântica, símbolos, referências e impacto de alterações.
7. Ler arquivos-fonte somente quando for necessário conhecer detalhes da implementação.
8. Usar **Context7** para documentação atualizada e específica de versão de frameworks, bibliotecas ou APIs.

Se o domínio da tarefa não tem documento em `docs/domain/`, ele ainda não foi redesenhado: suas regras não estão definidas e não devem ser inferidas do código ou da implementação legada. Perguntar ao usuário.

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
2. Consultar `docs/domain/overview.md` e o documento do domínio afetado.
3. Consultar `docs/adr/` se a alteração tocar uma decisão arquitetural.
4. Consultar Codebase Memory para localizar a área arquitetural afetada.
5. Usar Serena para localizar símbolos, referências e dependências relevantes.
6. Ler somente os arquivos necessários para validar a implementação atual.
7. Identificar testes existentes relacionados ao comportamento.
8. Consultar Context7 quando houver dúvida sobre comportamento de framework, biblioteca ou API.
9. Definir os arquivos que provavelmente serão alterados.
10. Identificar impactos arquiteturais, de banco e de testes.
11. Identificar as regras de negócio novas e as decisões arquiteturais que a tarefa vai gerar, e onde serão documentadas.
12. Produzir o plano somente depois dessa investigação direcionada.

O plano deve ser baseado no código e na arquitetura existentes, não em uma suposição de como o projeto deveria ser estruturado.

Não criar planos baseados em uma varredura completa do repositório quando a área afetada puder ser identificada por Codebase Memory e Serena.

## Documentação de novas decisões

Desenvolvimento que produz regra ou decisão nova produz documentação na mesma tarefa:

* Regra de negócio nova definida durante o desenvolvimento é documentada em `docs/domain/<dominio>.md`.
* Domínio novo ganha documento próprio em `docs/domain/`, e entra no índice do `overview.md`.
* Conceito que vale para mais de um domínio vai para o `overview.md`, não repetido em cada documento.
* Decisão arquitetural relevante vira uma ADR nova em `docs/adr/`, com o próximo número da sequência, e entra no índice.
* Regra não se duplica entre documentos: o overview não repete regra de domínio e o documento de domínio não repete conceito geral.
* A documentação descreve intenção e regra, não implementação. Nomes de classe, coluna e rota ficam fora do corpo do texto.
* Quando uma regra documentada antiga conflitar com uma decisão nova do usuário, a nova prevalece e a antiga é corrigida ou removida no mesmo momento — não coexistem.
* Regra ainda não decidida vai para a seção "Questões em aberto" do documento, como pergunta. Nunca inventar regra para preencher lacuna.

## Decisões de arquitetura fechadas

Referência rápida. Cada item tem sua ADR em [`docs/adr/`](docs/adr/README.md); não reabrir sem justificativa nova.

* **Auth = tabela de domínio**: `Usuario extends Authenticatable`, `$table = 'usuarios'`. Sem self-signup. — [ADR 0001](docs/adr/0001-auth-sobre-tabela-de-dominio.md)
* **Visibilidade via Eloquent, não RLS**: Global Scopes (`DonoScope`) + Policies. — [ADR 0002](docs/adr/0002-visibilidade-via-eloquent-sem-rls.md)
* **UUID via Eloquent** (`HasUuids`), não `DEFAULT gen_random_uuid()` no banco. — [ADR 0003](docs/adr/0003-uuid-via-eloquent.md)
* **Postgres real nos testes**, nunca SQLite. — [ADR 0004](docs/adr/0004-postgres-real-nos-testes.md)
* **Value Objects** para dinheiro e conceitos de domínio. — [ADR 0005](docs/adr/0005-value-objects-para-dinheiro.md)
* **Exclusão lógica com cascata na aplicação**. — [ADR 0006](docs/adr/0006-soft-delete-com-cascata-na-aplicacao.md)
* **Fechamento mensal / acerto / sobra**: removidos do domínio. — [ADR 0007](docs/adr/0007-fechamento-mensal-removido-do-dominio.md)

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

* `DonoScope` depende de `Auth::id()`. Sem usuário autenticado, toda query do model escopado devolve conjunto vazio — não erro. Processamento fora do ciclo de request (jobs, comandos, seeds) precisa de `withoutGlobalScope(DonoScope::class)` explícito, e remover o scope errado quebra o isolamento entre os usuários.
* Autorização: chamar `$this->authorize()` em cada método do controller. `authorizeResource` não funciona corretamente no Laravel 12 neste projeto.
* A cascata de exclusão lógica vive num hook do model pai e não dispara em exclusão feita fora do Eloquent.
