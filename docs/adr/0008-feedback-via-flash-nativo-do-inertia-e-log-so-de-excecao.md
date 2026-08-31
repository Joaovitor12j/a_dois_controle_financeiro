# 0008 — Feedback de ação via flash nativo do Inertia, log só de exceção não tratada

## Contexto

Uma ação do usuário podia falhar sem nenhum feedback visível na tela. Um caso real:
`StoreRendaRequest`/`UpdateRendaRequest` rejeitavam o `null` explícito que o frontend manda para
campos condicionais (ex.: `dia_recebimento` quando a recorrência é "única"). O erro existia
corretamente em `errors.dia_recebimento`, mas o `<InputError>` desse campo só é renderizado quando o
formulário está no ramo "mensal" — se estava no ramo "única", não havia nenhum elemento na tela para
mostrá-lo. Sem exceção lançada, sem log (`ValidationException` não é logada por padrão, corretamente),
só um redirect que parece sucesso a quem olha de fora.

Esse padrão — `useForm()` do Inertia sem qualquer feedback genérico — se repete nos 4 formulários de
domínio (Contas, Formas de Pagamento, Cartões de Crédito, Rendas). Sucesso também não tinha nenhuma
confirmação visível: existia uma prop `flash.status` na infraestrutura (`HandleInertiaRequests`), mas
nenhum controller a populava e nenhuma tela a lia — feature morta.

Em paralelo, o projeto não tinha nenhum uso de `Log::` no código e usava o canal `single` do Laravel,
sem rotação — `storage/logs/laravel.log` crescendo sem limite.

## Decisão

**Feedback ao usuário**, usando só o mecanismo nativo do Inertia v2 (sem lib de terceiros):

- Cada formulário ganha um resumo de erro (`FormErrorSummary`) que mostra todas as mensagens
  presentes em `errors`, incondicionalmente — não tenta detectar se o campo dono está visível. É uma
  rede de segurança agnóstica ao formulário: continua funcionando mesmo que uma regra nova crie o
  mesmo tipo de cenário no futuro.
- Sucesso e falha genérica (erro de rede, exceção inesperada, resposta que não é uma resposta Inertia
  válida) são comunicados por um toast global (`Toast`), montado uma vez no layout autenticado.
  Sucesso usa `Inertia::flash('toast', [...])` no backend e o evento `router.on('flash', ...)` no
  frontend; falha genérica usa os eventos `router.on('invalid', ...)` e `router.on('exception', ...)`.
- A mensagem de erro genérica do toast não descreve o problema técnico — detalhe fica só no log do
  servidor.

**Log**, usando só a stack nativa do Laravel:

- Canal `daily`, retenção de 14 dias (`LOG_DAILY_DAYS=14`) — troca o canal `single` sem rotação.
- Todo log de exceção carrega o `usuario_id` do usuário autenticado no momento, via
  `$exceptions->context()` em `bootstrap/app.php`.
- Só exceção não tratada (bug genuíno) é logada — é o comportamento padrão do Laravel, sem nenhuma
  configuração extra. Falha de validação, autorização negada e "não encontrado" **não** são forçadas
  para o log: são fluxo esperado da aplicação, já têm feedback na tela, e forçar isso exigiria lutar
  contra o `shouldReport()` interno do framework, gerando ruído sem sinal.

## Consequências

- Erro em campo condicional escondido nunca mais é silencioso — a rede de segurança não depende de
  saber qual campo é qual.
- O log só serve para identificar bug real, não para auditar tentativa inválida de usuário. Se isso
  vier a ser necessário (ex.: auditoria de tentativas de acesso indevido), é uma decisão nova e
  explícita, não implícita nesta.
- Quando o campo com erro já está visível, a mensagem aparece duas vezes (no `InputError` do campo e
  no resumo do formulário) — tradeoff aceito em troca de não precisar detectar dinamicamente
  visibilidade de campo.
- `flash.status` (prop manual, morta) foi removido de `HandleInertiaRequests`; o mecanismo de flash
  agora é exclusivamente o nativo do Inertia v2 (`page.flash`, não `page.props.flash`).

## Status

Aceita.
