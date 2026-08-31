# A Dois — Controle Financeiro — Contexto Completo do Projeto

> Snapshot gerado em 2026-08-31, a partir do código e da documentação atual do
> repositório. Serve para dar contexto pleno ao projeto do app no Claude
> (claude.ai). Onde código e `docs/` divergirem no futuro, `docs/` é a fonte
> de verdade — ver `CLAUDE.md`.

## 1. O que é o projeto

Sistema de controle financeiro para um casal (2 usuários fixos, sem
self-signup). É uma **migração/reescrita do zero** de uma aplicação anterior
em Next.js + Supabase + Drizzle para **Laravel + Breeze + Inertia + React**.
Banco novo, sem ETL. O domínio está sendo **redesenhado um domínio por vez**
— o que não tem documento em `docs/domain/` ainda não tem regra definida, e
nada deve ser inferido da implementação legada.

## 2. Stack e ambiente

* **Backend**: Laravel 12, PHP 8.3.
* **Frontend**: React (TypeScript) via Inertia.js v2 + Breeze.
* **Banco**: Postgres 17 (dependência real do domínio — CHECK constraints,
  ENUMs nativos, índices únicos parciais, FK compostas).
* **Infra local**: Docker custom (Dockerfile + docker-compose, sem Laravel
  Sail). Serviços: `app` (PHP-FPM), `nginx`, `postgres`.
  * Portas: app HTTP `8083` (nginx), Postgres `5487`, Vite (HMR) `5183`.
  * Vite roda **fora** do container, local, para hot reload.
  * `docker-compose.yml` monta bind mount completo (`.:/app`) — arquivos
    gerados (ex.: logs) aparecem direto no host.
* **Testes**: Pest, contra Postgres real via `.env.testing` — nunca
  SQLite/in-memory (ADR 0004).
* **Autenticação**: sessão via `SESSION_DRIVER=database`.
* **Seed**: 2 usuários fixos, semeados a partir de variáveis de ambiente
  (`USUARIO_1_*`, `USUARIO_2_*`) — só roda se a tabela `usuarios` estiver
  vazia; depois disso o banco é a fonte de verdade.
* **Logo de instituição financeira**: integração com LogoDev
  (`LOGODEV_PUBLISHABLE_KEY`), com cache (`LogoDevCacheService`,
  `LogoController`).

### Verificação local (não é CI)

```bash
composer check   # Pint (formatação) + PHPStan/Larastan (análise estática) + Pest (testes)
npm run build    # tsc (strict) + build Vite, para partes de frontend
```

## 3. Arquitetura

Clean Code / SOLID / DDD no contexto Laravel:

* **Controllers finos**, delegam a Services. Autorização feita chamando
  `$this->authorize()` explicitamente em cada método (NÃO usar
  `authorizeResource` — quebrado no Laravel 12 neste projeto).
* **Services** em `app/Services/Financeiro/` concentram a lógica de negócio
  de cada domínio (`ContaService`, `FormaPagamentoService`,
  `CartaoCreditoService`, `RendaService`).
* **Models finos** (Eloquent), em `app/Models/`.
* **Value Objects** para conceitos de domínio não-primitivos, em
  `app/Domain/ValueObjects/` — imutáveis, validam invariantes no
  construtor. Persistidos via Eloquent Casts customizados
  (`app/Casts/`).
* **Policies** em `app/Policies/`, autorizando ação sobre registro já
  carregado.
* **Enums nativos PHP 8.1+** com métodos, em `app/Enums/`.
* Sem comentários explicativos salvo quando fundamentais; sem try/catch
  salvo quando fundamental à lógica.

### Isolamento entre os dois usuários — via Eloquent, não RLS (ADR 0002)

Não há Row Level Security no Postgres. Isolamento em duas camadas
complementares:

* **Global Scope `DonoScope`** (`app/Models/Scopes/DonoScope.php`): aplicado
  via atributo `#[ScopedBy(DonoScope::class)]` nos models que têm
  `usuario_id` direto (`Conta`, `Renda`). Filtra a query por
  `usuario_id = Auth::id()`; **sem usuário autenticado, devolve conjunto
  vazio** (`whereRaw('false')`), nunca erro.
* **Policies**: autorizam a operação depois que o scope já filtrou. Para
  entidades sem `usuario_id` próprio (`FormaPagamento`, `CartaoCredito`),
  a policy verifica posse subindo até `Conta`, usando
  `Conta::withoutGlobalScope(DonoScope::class)` (necessário porque nesse
  ponto o registro já foi carregado e o scope da Conta do dono aplicaria
  de novo sobre o usuário errado se não for removido).

**Efeito de produto**: um registro do parceiro se comporta como
**inexistente (404)**, nunca como proibido (403) — o scope o remove antes
da policy ser avaliada.

**Ponto de atenção crítico**: processamento fora do ciclo de request (jobs,
comandos artisan, seeds) não tem `Auth::id()` — precisa de
`withoutGlobalScope(DonoScope::class)` explícito. Remover o scope errado
quebra o isolamento entre os dois usuários.

### Outras decisões de arquitetura fechadas (ADRs, `docs/adr/`)

| # | Decisão | Resumo |
|---|---|---|
| 0001 | Auth sobre tabela de domínio | `Usuario extends Authenticatable`, tabela `usuarios`. Não existe tabela `users` do Breeze. Sem self-signup, sem recuperação de senha por auto-serviço, sem verificação de e-mail. Criar usuário é seed, não feature. |
| 0002 | Visibilidade via Eloquent, não RLS | Ver seção acima. |
| 0003 | UUID via Eloquent | PK `uuid` gerado pela aplicação via trait `HasUuids`, sem `DEFAULT gen_random_uuid()` no banco. Insert fora do Eloquent (SQL cru, fixtures) precisa fornecer o id. |
| 0004 | Postgres real nos testes | Nunca SQLite. Testes mais lentos, mas validam CHECK/ENUM/índice único/FK real. |
| 0005 | Value Objects para dinheiro | Dinheiro é sempre `Money`, nunca float/int solto. VO imutável, valida no construtor. |
| 0006 | Soft delete com cascata na aplicação | Exclusão lógica (`SoftDeletes`) tem cascata implementada em hook do model pai (`booted()` → evento `deleted`), não pelo banco. `ON DELETE CASCADE` do banco continua existindo só como rede de segurança para exclusão física (`forceDelete`). Filho arrastado não sabe que foi arrastado; restaurar o pai não restaura os filhos automaticamente (comportamento não implementado ainda). |
| 0007 | Fechamento mensal/acerto/sobra removidos | Conceitos da versão Next.js antiga, com cálculo incorreto. Removidos do domínio deliberadamente — não devem ser recriados a partir do legado. |
| 0008 | Feedback via flash nativo do Inertia, log só de exceção | Ver seção 6. |

## 4. Domínio — regras de negócio (fonte de verdade: `docs/domain/`)

### 4.1 Conceito geral (`overview.md`)

Dois contextos de movimentação, ainda não totalmente reconciliados no
código:

* **Movimentação individual**: pertence exclusivamente ao usuário que
  criou; parceiro não visualiza/consulta/interfere.
* **Movimentação conjunta**: pertence ao casal, visível a ambos.

**Questão em aberto**: como o contexto conjunto se materializa
financeiramente (fora do conceito de conta? rateio sobre contas
individuais? outro mecanismo?) — **não decidido ainda**.

### 4.2 Contas (`contas.md`)

* Agrupador nomeado de formas de pagamento e cartões de crédito de **um**
  usuário. **Não tem saldo próprio.**
* **Sempre individual** — não existe conta conjunta.
* Pertence a exatamente um usuário; conta do parceiro é inexistente (404),
  não proibida (403).
* Identificada por `nome` (obrigatório). Sempre listada em ordem alfabética
  por nome.
* Exclusão lógica arrasta (também logicamente) todas as formas de pagamento
  e cartões de crédito da conta. Exclusão definitiva (`forceDelete`) não
  arrasta por essa regra de aplicação (a FK do banco resolve fisicamente).
* Em aberto: nome duplicado por usuário permitido hoje; não há restauração;
  efeito de eventual restauração sobre os filhos arrastados não decidido.

### 4.3 Formas de pagamento (`formas-pagamento.md`)

* Meio pelo qual dinheiro entra/sai de uma conta: **débito, dinheiro ou
  pix** (enum `tipo_forma_pagamento` no Postgres / `TipoFormaPagamento` no
  PHP).
* Pertence a exatamente uma conta (e por herança ao dono da conta). Não
  existe sem conta.
* Identificada por `nome` (obrigatório) + `tipo` (obrigatório).
* **Saldo inicial** (opcional, em centavos): quando informado na criação,
  vira uma `Movimentacao` marcada `is_saldo_inicial = true`, na data
  informada. **Imutável** depois de criado — sem edição de saldo inicial.
* Exclusão lógica, mesma regra geral do domínio.
* Em aberto: exclusão de forma de pagamento **não** arrasta as
  movimentações associadas (assimétrico com conta → forma de pagamento);
  edição de `tipo` com movimentações existentes é permitida hoje, mas não
  decidido se deveria ser restrita.

### 4.4 Cartões de crédito (`cartoes-credito.md`)

* Meio de pagamento com limite e ciclo de fatura próprios. Pertence a
  exatamente uma conta.
* Identificado por `nome` (obrigatório).
* `limite_total` (obrigatório, centavos). `limite_usado_abertura`
  (opcional, centavos, default 0) representa limite já comprometido no
  momento em que o cartão passa a ser controlado pelo sistema —
  **imutável**, campo próprio do cartão, **não** gera `Movimentacao`
  (diferente do saldo inicial de forma de pagamento).
* `dia_fechamento` e `dia_vencimento`: ambos obrigatórios, inteiros entre 1
  e 31 (CHECK constraint no banco).
* Exclusão lógica, mesma regra geral.
* Em aberto: relação cartão↔fatura ainda não redesenhada (o model já tem
  `hasMany(Fatura)`, mas isso é implementação legada, não regra
  confirmada); nada valida hoje que vencimento seja depois do fechamento;
  não há via de correção para erro de cadastro no limite de abertura.

### 4.5 Rendas (`rendas.md`)

* Entrada financeira do usuário, vinculada a uma conta e a uma categoria de
  renda. Pertence a exatamente um usuário e a exatamente uma conta.
* Posse direta (quem criou), não por herança da conta. Renda de outro
  usuário é inexistente (404), não proibida.
* Identificada por `descricao` (obrigatória) + `valor` (obrigatório,
  centavos, **sempre > 0**).
* **Categoria de renda**: entidade **compartilhada entre os dois usuários**
  (não é por conta/usuário). Ainda sem documento de domínio próprio — regras
  de quem pode criar/editar categoria não definidas.
* **Recorrência** (`tipo_recorrencia`: `unica` | `mensal`), com campos
  mutuamente exclusivos validados por CHECK constraint no Postgres:
  * `unica`: exige `data_recebimento`; proíbe `dia_recebimento`,
    `data_inicio`, `data_fim`.
  * `mensal`: exige `dia_recebimento` (1–31) e `data_inicio`; proíbe
    `data_recebimento`; `data_fim` opcional e, se informada, não pode ser
    anterior a `data_inicio` (validação de aplicação, não vista em CHECK).
* Em aberto: como renda mensal gera movimentações ao longo do tempo — não
  redesenhado ainda.

### 4.6 Domínios ainda não redesenhados

`Fatura` e `Movimentacao` existem como tabela/model (herdados do legado),
mas **não têm documento em `docs/domain/`** — logo, suas regras de negócio
**não estão definidas**. A existência da tabela, migration, model ou tela
não constitui regra de negócio (ver `overview.md`, seção "Estado do
redesenho"). Não inferir regra do código legado ou do schema.

## 5. Modelagem de dados (schema atual, Postgres)

Todas as tabelas de domínio usam PK `uuid` gerada pela aplicação (não pelo
banco — ADR 0003).

### `usuarios`
| Coluna | Tipo | Notas |
|---|---|---|
| id | uuid PK | |
| nome | string | |
| email | string | unique |
| password | string | hashed |
| cor | string(7) | ex. `#RRGGBB` |
| remember_token | | |
| timestamps | | |

Entidade de auth (`Usuario extends Authenticatable`). Sem tabela `users`.

### `contas`
| Coluna | Tipo | Notas |
|---|---|---|
| id | uuid PK | |
| usuario_id | uuid FK → usuarios | `cascadeOnDelete` (física) |
| nome | string | |
| timestamps + deleted_at | | soft delete |

Model tem `#[ScopedBy(DonoScope::class)]`, `SoftDeletes`, e hook `booted()`
que na exclusão lógica (não force) dispara `delete()` em cascata sobre
`formasPagamento()` e `cartoesCredito()`. Attribute computado `logo_url`
(`route('logos.show', $nome)`), sempre appended.

### `formas_pagamento`
| Coluna | Tipo | Notas |
|---|---|---|
| id | uuid PK | |
| conta_id | uuid FK → contas | `cascadeOnDelete` (física) |
| nome | string | |
| tipo | enum Postgres `tipo_forma_pagamento` (`debito`,`dinheiro`,`pix`) | cast p/ `TipoFormaPagamento` |
| timestamps + deleted_at | | soft delete |

### `cartoes_credito`
| Coluna | Tipo | Notas |
|---|---|---|
| id | uuid PK | |
| conta_id | uuid FK → contas | `cascadeOnDelete` (física) |
| nome | string | |
| limite_total | bigint (centavos) | cast `MoneyCast` → `Money` |
| limite_usado_abertura | bigint (centavos), default 0 | cast `MoneyCast` → `Money`, imutável |
| dia_fechamento | smallint | CHECK 1–31 |
| dia_vencimento | smallint | CHECK 1–31 |
| timestamps + deleted_at | | soft delete |

### `faturas` (não redesenhado — legado)
| Coluna | Tipo | Notas |
|---|---|---|
| id | uuid PK | |
| cartao_credito_id | uuid FK → cartoes_credito | `cascadeOnDelete` (física) |
| competencia | date | cast `CompetenciaCast` → VO `Competencia` (ano/mês); CHECK dia = 1 |
| timestamps | | sem soft delete |

Unique composto `(cartao_credito_id, competencia)`.

### `movimentacoes` (não redesenhado — legado)
| Coluna | Tipo | Notas |
|---|---|---|
| id | uuid PK | |
| forma_pagamento_id | uuid FK → formas_pagamento | `restrictOnDelete` |
| valor | bigint (centavos) | cast `MoneyCast` → `Money` |
| data | date | |
| despesa_id | uuid nullable | sem FK declarada (entidade "despesa" não existe ainda) |
| renda_id | uuid nullable FK → rendas | `restrictOnDelete` (FK adicionada em migration separada, depois de `rendas` existir) |
| fatura_id | uuid nullable FK → faturas | `restrictOnDelete` |
| is_saldo_inicial | boolean, default false | |
| timestamps | | sem soft delete |

Constraints Postgres:
* CHECK `num_nonnulls(despesa_id, renda_id, fatura_id) <= 1` — origem única
  (movimentação só pode vir de no máximo uma origem entre despesa/renda/
  fatura).
* CHECK `is_saldo_inicial = false OR num_nonnulls(...) = 0` — saldo inicial
  não pode ter origem.
* Índice único parcial `movimentacoes_saldo_inicial_unico` em
  `forma_pagamento_id` `WHERE is_saldo_inicial` — no máximo uma
  movimentação de saldo inicial por forma de pagamento.
* Índice normal em `(forma_pagamento_id, data)`.

### `categorias_renda`
| Coluna | Tipo | Notas |
|---|---|---|
| id | uuid PK | |
| nome | string | |
| cor | string | |
| icone | string | |
| timestamps | | sem soft delete, sem `usuario_id` — compartilhada entre os 2 usuários |

### `rendas`
| Coluna | Tipo | Notas |
|---|---|---|
| id | uuid PK | |
| usuario_id | uuid FK → usuarios | `cascadeOnDelete` (física) |
| conta_id | uuid FK → contas | `restrictOnDelete` |
| categoria_renda_id | uuid FK → categorias_renda | `restrictOnDelete` |
| descricao | string | |
| valor | bigint (centavos) | cast `MoneyCast` → `Money`, sempre > 0 (regra de aplicação) |
| tipo_recorrencia | enum Postgres `tipo_recorrencia_renda` (`unica`,`mensal`) | cast → `TipoRecorrencia` |
| data_recebimento | date nullable | só p/ `unica` |
| dia_recebimento | smallint nullable | só p/ `mensal`, CHECK 1–31 |
| data_inicio | date nullable | só p/ `mensal` |
| data_fim | date nullable | só p/ `mensal`, opcional |
| timestamps | | sem soft delete |

CHECK `rendas_recorrencia_datas_check`: garante exclusividade dos campos de
data conforme `tipo_recorrencia` (ver seção 4.5). Model tem
`#[ScopedBy(DonoScope::class)]`.

### Tabelas de infraestrutura Laravel padrão
`sessions` (FK `user_id` → usuarios), `cache`, `cache_locks`, `jobs`,
`job_batches`, `failed_jobs` — sem regra de domínio.

### Diagrama de relacionamento (visão geral)

```
usuarios 1──N contas 1──N formas_pagamento 1──N movimentacoes
                    │                              │  (restrict)
                    └──N cartoes_credito 1──N faturas 1──N movimentacoes
                                                                │ (restrict)
usuarios 1──N rendas N──1 categorias_renda (compartilhada)     │
              │                                                 │
              └──1 conta (restrict)                             │
              └──N movimentacoes ─────────────────────────────┘ (restrict, via renda_id)
```

Cascata **lógica** (soft delete, hook de aplicação): `conta` → `formas_pagamento`
+ `cartoes_credito`. Não vai além disso (não desce para `movimentacoes` nem
para `faturas`).

## 6. Value Objects, Casts e Enums

* **`Money`** (`app/Domain/ValueObjects/Money.php`): readonly, guarda
  `cents` (int). Fábricas `fromCents`, `zero`, `fromString` (parse
  BR: vírgula decimal, ponto de milhar). Operações `plus/minus/times/
  negated/absolute`, comparações `isNegative/isPositive/isZero/equals`.
  `JsonSerializable` → serializa como int (centavos). Persistido via
  `MoneyCast` (`app/Casts/MoneyCast.php`).
* **`Competencia`** (`app/Domain/ValueObjects/Competencia.php`): readonly,
  ano+mês. Fábricas `deAnoMes`, `deData`, `deString` ("YYYY-MM"). Operações
  `somarMeses/proxima/anterior/paraData`, comparações
  `equals/ehAnterior/ehPosterior`. `__toString` → "YYYY-MM". Persistido via
  `CompetenciaCast`.
* **`TipoFormaPagamento`** (enum string): `Debito`, `Dinheiro`, `Pix`, com
  método `rotulo()`.
* **`TipoRecorrencia`** (enum string): `Unica`, `Mensal`.
* Exceções de domínio em `app/Domain/Exceptions/`: `DominioException`
  (base), `ValorMonetarioInvalido`, `CompetenciaInvalida`.

## 7. Feedback ao usuário e logging (ADR 0008)

Mecanismo **só nativo do Inertia v2**, sem lib de terceiros.

* **Erro de validação por campo**: `errors.<campo>` + `<InputError>` como
  sempre, **mais** `<FormErrorSummary errors={errors} />` em todo formulário
  — mostra qualquer erro presente em `errors`, incondicionalmente,
  independente de qual campo (visível ou escondido por renderização
  condicional) o carrega. Corrige um bug real: erro em campo escondido por
  branch condicional (ex.: `dia_recebimento` quando recorrência é "única")
  ficava sem nenhum feedback visível.
* **Sucesso e falha genérica** (rede caiu, exceção inesperada, resposta que
  não é Inertia válida): `Toast` global, montado em
  `AuthenticatedLayout`.
  * Sucesso: controller chama `Inertia::flash('toast', ['type' =>
    'success', 'message' => '...'])` antes do `Redirect::route(...)`.
  * Frontend lê via `usePage().flash.toast` — **não**
    `usePage().props.flash.toast` (flash nativo do Inertia v2 é campo de
    nível raiz de `page`, irmão de `props`).
  * Falha genérica: eventos `router.on('invalid', ...)` e
    `router.on('exception', ...)`.
  * Mensagem genérica de erro não descreve o problema técnico — detalhe
    fica só no log do servidor.
* **Log**: canal `daily`, retenção `LOG_DAILY_DAYS=14`
  (`storage/logs/laravel-YYYY-MM-DD.log`). Todo log de exceção carrega
  `usuario_id` do usuário autenticado (`$exceptions->context()` em
  `bootstrap/app.php`).
  * Loga **só exceção não tratada** (bug genuíno) — comportamento padrão
    do Laravel, sem `report()`/`level()` customizado.
  * **Não** loga: `ValidationException`, `AuthorizationException`,
    `ModelNotFoundException` — fluxo esperado, já vira mensagem na tela.
  * Acesso: bind mount completo no docker-compose — arquivo já existe no
    host em `storage/logs/`.
    * `tail -f storage/logs/laravel-$(date +%Y-%m-%d).log`
    * Tempo real no container: `docker compose exec -u 1000:1000 app php artisan pail`

## 8. Rotas e telas (estado atual)

`routes/web.php`, tudo sob middleware `auth` (exceto `/` que redireciona):

* `/dashboard` → `Dashboard` (Inertia, sem controller dedicado ainda).
* `/logos/{nome}` → `LogoController@show` (proxy/cache de logo via LogoDev).
* `/profile` (`ProfileController`): editar perfil (Breeze).
* `Route::resource('contas', ContaController)` — sem `create/edit/show`
  (CRUD feito via modais/páginas React, não páginas dedicadas).
* `Route::resource('formas-pagamento', FormaPagamentoController)` — sem
  `index/create/edit/show`; parâmetro nomeado `formaPagamento`.
* `Route::resource('cartoes-credito', CartaoCreditoController)` — sem
  `index/create/edit/show`; parâmetro nomeado `cartaoCredito`.
* `Route::resource('rendas', RendaController)` — sem `create/edit/show`.

Frontend (`resources/js/Pages/`):
* `Auth/Login.tsx`, `Profile/Edit.tsx` (+ partials) — Breeze padrão.
* `Contas/Index.tsx` + partials: `CartaoConta`, formulários
  (`FormularioConta`, `FormularioFormaPagamento`, `FormularioCartaoCredito`)
  e modais de confirmação de exclusão para os três.
* `Rendas/Index.tsx` + partials: `FormularioRenda`,
  `ConfirmarExclusaoRenda`.
* `Dashboard.tsx` — placeholder.
* Componentes de apoio: `FormErrorSummary`, `Toast` (novos, do ADR 0008),
  `LogoEmpresa`, e os componentes padrão do Breeze
  (`TextInput`, `SelectInput`, `InputError`, `Modal`, `Dropdown`, etc).

## 9. Testes

* Pest, Postgres real (`.env.testing`).
* Feature test = HTTP + banco (`tests/Feature/ContaTest.php`,
  `FormaPagamentoTest.php`, `RendaTest.php`) — cobrem os domínios já
  redesenhados.
* Unit test = função/VO puro sem framework (ex.: os VOs `Money` e
  `Competencia` deveriam ter teste unitário próprio, validando invariantes
  e imutabilidade).
* Padrão de projeto: `composer check` roda Pint → PHPStan/Larastan → Pest,
  todos precisam passar.

## 10. Pontos de atenção conhecidos (armadilhas recorrentes)

1. **`DonoScope` depende de `Auth::id()`.** Sem usuário autenticado, toda
   query de model escopado devolve vazio, não erro. Jobs/comandos/seeds
   precisam de `withoutGlobalScope(DonoScope::class)` explícito.
2. **Autorização por método, não por `authorizeResource`.** Chamar
   `$this->authorize()` em cada método do controller — `authorizeResource`
   não funciona corretamente no Laravel 12 deste projeto.
3. **Cascata de exclusão lógica só via Eloquent.** Vive num hook do model
   pai (`Conta::booted()`); não dispara se a exclusão for feita fora do
   Eloquent (SQL cru, etc.).
4. **`flash.toast` é raiz de `page`, não `props`.** `usePage().flash.toast`,
   nunca `usePage().props.flash.toast`.
5. **Domínios sem documento (`Fatura`, `Movimentacao`) não têm regra
   definida** — não inferir do schema/model legado.

## 11. Questões em aberto do domínio (consolidado)

* Como o contexto de movimentação **conjunta** do casal se materializa
  financeiramente (overview.md).
* Nome de conta duplicado por usuário — permitir ou não.
* Restauração de conta excluída logicamente — existe ou não; efeito sobre
  filhos arrastados.
* Cascata lógica de forma de pagamento → movimentações — deveria existir?
* Edição de `tipo` de forma de pagamento com movimentações existentes —
  deveria ser restrita?
* Relação cartão de crédito ↔ fatura — ainda não redesenhada.
* Consistência entre `dia_fechamento` e `dia_vencimento` do cartão — validar
  ordem ou não.
* Via de correção para erro de cadastro em `limite_usado_abertura`.
* Geração de movimentações a partir de renda recorrente mensal.
* Regras de categoria de renda (quem cria/edita, é compartilhada — já se
  sabe que sim, mas falta o resto).

---

*Este documento é um retrato do estado do repositório em 2026-08-31. Para o
estado corrente, sempre preferir `docs/domain/`, `docs/adr/` e o código —
este arquivo não é atualizado automaticamente.*
