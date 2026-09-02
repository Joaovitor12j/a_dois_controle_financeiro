# 0013 — Agregação casal no dashboard: bypass pontual do DonoScope

## Contexto

O dashboard tem um modo de visualização "Casal" que soma a receita dos dois usuários fixos do
sistema — ver [docs/domain/dashboard.md](../domain/dashboard.md). `Renda` é protegida por
`DonoScope` ([ADR 0002](0002-visibilidade-via-eloquent-sem-rls.md)), que restringe toda consulta ao
usuário autenticado. Sem uma exceção explícita, não há como um usuário autenticado consultar a
renda do parceiro para compor esse total, mesmo em modo de leitura agregada.

Despesa não precisa dessa exceção: `DespesaScope` já libera despesas de contexto conjunta para
qualquer usuário autenticado, então a soma de despesa conjunta em modo Casal funciona com o scope
normal.

Existe uma segunda exceção do mesmo tipo, encontrada ao implementar "Contribuição por pessoa": quem
pagou uma despesa conjunta deriva de `movimentação → forma de pagamento → conta → usuário`
([movimentacoes.md](../domain/movimentacoes.md#pagamento-de-despesa)). `Conta` também é protegida
por `DonoScope`, então, sem bypass, a `conta` do parceiro nessa cadeia vem `null` sempre que ele foi
quem pagou — mesmo a despesa e a movimentação já sendo visíveis ao usuário autenticado. Isso não é
uma decisão nova de visibilidade: o dado já é visível, só a leitura em cadeia é que precisa ignorar
o scope para não quebrar.

Essa segunda exceção não é exclusiva do dashboard: `DespesaController::index` (tela de Despesas)
tem o mesmo problema — "Pago por" numa despesa conjunta paga pelo parceiro, e o próprio cartão de
uma despesa parcelada criada pelo parceiro, vinham `null` pelo mesmo motivo. Foi corrigido junto,
no mesmo padrão.

## Decisão

`App\Services\Financeiro\DashboardService`, e somente ele, consulta `Renda` com
`withoutGlobalScope(DonoScope::class)`, restringindo explicitamente o resultado a
`whereIn('usuario_id', $idsDosDoisUsuarios)` — nunca uma consulta irrestrita. Esse bypass só ocorre
quando o modo de visualização é "Casal"; em modo "Individual" a consulta usa o scope normal.

`DashboardService` e `DespesaController::index` também carregam `formaPagamento.conta` e
`movimentacoes.formaPagamento.conta` com `withoutGlobalScope(DonoScope::class)` na relação `conta`,
para que "quem pagou" (e o cartão de uma despesa parcelada) resolvam corretamente independentemente
de qual dos dois usuários está autenticado — sem essa exceção, a resolução falharia silenciosamente
(retornando `null`) sempre que o dono da conta fosse o parceiro.

Não há entidade "casal" no domínio — os dois usuários são fixos, definidos pelo seeder
(`database/seeders/DatabaseSeeder.php`) a partir de `config('usuarios.iniciais')`
([overview.md](../domain/overview.md)). "Os dois usuários" é sempre quem tem um desses e-mails
configurados — nunca `Usuario::all()` puro, que pegaria também qualquer registro estranho que
exista na tabela fora do fluxo do seeder.

## Consequências

- O bypass de `Renda` continua exclusivo do `DashboardService`, restrito ao modo Casal. Nenhuma
  outra tela reabre esse: uma tela nova que precise ver renda do parceiro repete o padrão
  explicitamente, no seu próprio service, e não generaliza o bypass para todo `Renda`.
- O bypass de `Conta` na cadeia `formaPagamento.conta` (e `movimentacoes.formaPagamento.conta`) é
  mais amplo: qualquer leitura que precise resolver dono de conta a partir de uma despesa ou
  movimentação já visível deve usá-lo, não só o dashboard. Continua restrito a essa cadeia
  específica — não bypassa `DonoScope` em consultas diretas a `Conta`.
- Continua valendo o princípio de isolamento do ADR 0002 para toda escrita e para qualquer consulta
  direta a `Conta` ou `Renda` fora desses pontos: dado do parceiro nunca aparece em telas de CRUD
  além do que já era visível por `DespesaScope`.

## Status

Aceita.
