# Dashboard (Visão geral)

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são repetidos aqui.

## Conceito

O dashboard é uma tela de leitura agregada sobre rendas e despesas de um período (mês/ano). Não
introduz dado próprio: tudo o que exibe deriva de renda, despesa e movimentação já existentes.

## Modo de visualização

O usuário escolhe entre **Individual** e **Casal**.

- **Individual**: mostra só o que é do usuário autenticado — suas rendas e suas despesas visíveis
  (individuais dele + conjuntas, que já são visíveis a qualquer um dos dois por
  [ADR 0010](../adr/0010-visibilidade-de-despesa-contexto-individual-conjunta.md)).
- **Casal**: soma a renda dos dois usuários fixos do sistema (ver
  [ADR 0013](../adr/0013-agregacao-casal-no-dashboard-bypass-pontual-donoscope.md) para o mecanismo
  de leitura que isso exige) e soma **só a despesa de contexto conjunta** — despesa individual de
  qualquer um dos dois fica de fora do total em modo Casal.

Não existe entidade "casal" no domínio: os dois usuários são fixos, definidos pelo seeder a partir
de `config('usuarios.iniciais')` ([overview.md](overview.md)). "Os dois usuários" é sempre quem tem
um desses e-mails configurados — não "toda linha da tabela `usuarios`". Um registro estranho na
tabela (por exemplo, criado à mão fora do fluxo do seeder) não entra na soma do modo Casal.

## Saldo do período

**"Saldo" nesta tela não é saldo bancário real.** Um saldo de conta de verdade dependeria de renda
gerar movimentação e de saldo inicial estar coberto pelo redesenho de movimentação — nenhum dos dois
está redesenhado ainda (ver [movimentacoes.md](movimentacoes.md)).

Por isso, "saldo" é definido como o **resultado acumulado dentro do período selecionado**: a soma
das rendas e despesas do mês, dia a dia, começando do zero no primeiro dia do período. Isso é
diferente de "Resultado" (receita total do período − despesa total do período): saldo reflete só o
que já é certo até o ponto de corte; resultado é a soma completa do período, incluindo o que ainda
não aconteceu.

Ponto de corte entre "realizado" e "projetado":

- Período é o mês atual → corte é hoje.
- Período é um mês passado → corte é o último dia do mês (tudo pôde ter acontecido).
- Período é um mês futuro → corte é antes do primeiro dia (nada é realizado ainda).

Um evento entra na linha **realizada** só quando é certo: renda, só quando já está recebida (existe
movimentação de recebimento na competência), pelo dia do recebimento; despesa, só quando já está
paga (existe movimentação na competência), pelo dia do pagamento. Uma renda ou despesa **pendente é
sempre projeção**, nunca realizado, mesmo que seu dia agendado já tenha passado — renda projetada
entra pelo dia agendado de recebimento, despesa projetada entra pelo dia de vencimento.

**Despesa parcelada não entra na evolução diária do saldo, nem em Pendências, nem em Alertas** —
ela não tem data de vencimento própria (esse dado pertence à fatura, e fatura ainda não foi
redesenhada). Ela continua entrando nos totais do período e em "Despesa por categoria".

## Pendências e alertas

"Pendências" lista despesas única/mensal e rendas do período que ainda não têm movimentação (de
pagamento ou de recebimento, respectivamente) na competência, misturadas numa única lista ordenada
por data — vencimento para despesa, dia agendado de recebimento para renda.

"Alertas" deriva das mesmas pendências: entra quem vence/deveria receber em até 7 dias a partir de
hoje, incluindo o que já passou do prazo sem pagamento ou recebimento.

## Contribuição por pessoa

Só existe em modo Casal. Duas medidas, por usuário:

- **Receita aportada**: soma da renda de cada usuário que já tem movimentação de recebimento na
  competência — só a recebida, não a agendada. Mesmo princípio de "Despesa conjunta paga".
- **Despesa conjunta paga**: soma das despesas de contexto conjunta que já têm movimentação na
  competência, agrupada por quem pagou — quem pagou deriva de
  `movimentação → forma de pagamento → conta → usuário`, mesma regra de
  [movimentacoes.md](movimentacoes.md#pagamento-de-despesa).

## Filtros

O dashboard aceita os mesmos quatro filtros de despesa definidos em
[despesas.md](despesas.md#filtros): categoria, tipo de lançamento, forma de
pagamento e status de pagamento. Eles restringem o universo de despesas
usado no cálculo de saldo, pendências, alertas, despesa por categoria e
contribuição por pessoa — dentro do período e do modo (Individual/Casal) já
selecionados. Não alteram a definição de saldo, o corte realizado/projetado
nem nenhuma outra regra já descrita neste documento; apenas reduzem o
conjunto de despesas considerado.

Renda não é afetada por esses filtros — eles são exclusivamente sobre
despesa.

## Questões em aberto

- **Cartão de crédito no dashboard.** Fora do escopo desta tela por ora — fatura ainda não foi
  redesenhada (mesma lacuna de [despesas.md](despesas.md) e
  [formas-pagamento.md](formas-pagamento.md)).
- **Vencimento de despesa parcelada.** Depende da fatura ser redesenhada; até lá, parcelada não
  aparece em Pendências, Alertas nem na evolução diária do saldo.

---

Implementado em: `app/Domain/Financeiro/CalculadoraOcorrenciaRenda.php`,
`app/Services/Financeiro/DashboardService.php`, `app/Http/Controllers/DashboardController.php`.
