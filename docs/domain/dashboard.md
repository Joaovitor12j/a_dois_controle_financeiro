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

## Realizado e Previsto

**Não é saldo bancário real.** Um saldo de conta de verdade dependeria de saldo inicial estar
coberto pelo redesenho de movimentação — isso ainda não está redesenhado (ver
[movimentacoes.md](movimentacoes.md)).

Por isso, a tela expõe dois números distintos para o período:

- **Realizado** — resultado acumulado dentro do período selecionado: a soma das rendas e despesas
  do mês, dia a dia, começando do zero no primeiro dia do período, até o ponto de corte. Rótulo
  varia com o período: mês atual → "Realizado até hoje"; mês passado → "Realizado no mês"; mês
  futuro → o número não se aplica (nada pôde ter acontecido ainda) e o card informa isso em vez de
  exibir zero.
- **Previsto** — soma completa do período (receita total − despesa total), incluindo o que ainda
  não aconteceu. Corresponde ao que antes era chamado de "Resultado"; o cálculo não mudou, só o
  nome.

Ponto de corte entre Realizado e Previsto:

- Período é o mês atual → corte é hoje.
- Período é um mês passado → corte é o último dia do mês (tudo pôde ter acontecido).
- Período é um mês futuro → corte é antes do primeiro dia (nada é realizado ainda).

Um evento entra na linha **realizada** só quando é certo: renda, só quando já está recebida (existe
movimentação de recebimento na competência), pelo dia do recebimento; despesa, só quando já está
paga (existe movimentação na competência), pelo dia do pagamento. Uma renda ou despesa **pendente é
sempre projeção**, nunca realizado, mesmo que seu dia agendado já tenha passado — renda projetada
entra pelo dia agendado de recebimento, despesa projetada entra pelo dia de vencimento.

**Despesa parcelada não entra na evolução diária do saldo, nem em Pendências** — ela não tem data
de vencimento própria (esse dado pertence à fatura, e fatura ainda não foi redesenhada). Ela
continua entrando nos totais do período, em "Despesa por categoria" e em "Despesa por forma de
pagamento". A tela avisa essa limitação junto da evolução diária, quando há alguma parcelada no
período.

## Badge de variação sobre o mês anterior

Cada um dos quatro números do resumo (Realizado, Renda, Despesa, Previsto) pode exibir um selo
comparando com o mesmo número no mês anterior. A regra é igual para os quatro — não existe
indicador que apareça em uns e não em outros por causa da regra em si (só por não haver dado no
mês anterior, caso coberto abaixo):

- Quando o mês anterior tem base **igual ou maior que R$ 50,00** para aquele número, o selo mostra
  variação percentual.
- Quando a base do mês anterior é **menor que R$ 50,00** (incluindo zero), percentual não é
  significativo — o selo mostra a variação absoluta em reais.
- Quando o número atual e o do mês anterior são **os dois zero**, não há nada para comparar e o
  selo não aparece.

A cor do selo segue o significado financeiro, não o sinal aritmético: aumento de despesa é
atenção (tom vinho); aumento de renda, de realizado e de previsto é positivo (tom verde).

## Pendências

"Pendências" lista despesas única/mensal e rendas do período que ainda não têm movimentação (de
pagamento ou de recebimento, respectivamente) na competência, misturadas numa única lista ordenada
por data — vencimento para despesa, dia agendado de recebimento para renda.

Cada pendência carrega um nível de criticidade, calculado a partir da distância até hoje:
**vencida** (prazo já passou sem pagamento/recebimento), **vence em breve** (vence ou deveria
receber em até 7 dias a partir de hoje, incluindo o que já venceu) ou **no prazo** (mais de 7 dias
à frente). O nível é só um estado visual da própria linha — não existe mais uma seção separada de
"Alertas" com os mesmos itens: era a mesma informação repetida em dois lugares. O cabeçalho do card
soma quantos itens estão vencidos ou vencendo em breve.

Cada pendência de despesa tem uma ação para marcar como paga diretamente da lista, sem sair da
tela. A ação abre o mesmo formulário e aciona o mesmo serviço de pagamento usado na tela de
Despesas ([ADR 0012](../adr/0012-pagamento-de-despesa-como-movimentacao.md)) — nenhuma regra nova
de pagamento é criada aqui, e nada muda no formato da movimentação gerada. Depois da ação, resumo,
evolução do saldo, categorias e contribuição por pessoa do período refletem o pagamento. Isso
significa que a tela deixa de ser só leitura: ela dispara uma ação sobre despesa existente, embora
continue sem guardar dado próprio (nenhuma informação nasce ou vive só no dashboard).

## Individual x Conjunta

Só existe em modo Individual. Divide o total de despesa do usuário autenticado (que em modo
Individual já mistura individual + conjunta, ver "Modo de visualização") entre as duas partes:
quanto é despesa individual dele e quanto é despesa conjunta. Usa o mesmo universo de despesa já
filtrado do resumo — não é um cálculo novo, é outro agrupamento do mesmo total.

## Categorias

"Despesa por categoria" e "Renda por categoria" agrupam pelo total de cada categoria no período,
ordenadas da maior para a menor. Mostram até **4 categorias** individualmente; a partir da 5ª, o
restante entra numa linha agregada "Outras", em vez de poluir a lista com muitas linhas pequenas —
só quando sobram pelo menos duas categorias além das 4 (uma só, sozinha, não é agrupada; nesse caso
a lista mostra 5 categorias e não gera "Outras"). "Despesa por categoria" também mostra, por
categoria, quanto já está pago e quanto ainda está pendente, na competência do período — mesmo
critério de pagamento usado no resto da tela (existência de movimentação na competência).

## Despesa por forma de pagamento

Mesmo princípio de "Despesa por categoria", agrupando pela forma de pagamento em vez da categoria:
para despesa parcelada, a forma de pagamento é a própria (o cartão da compra); para única/mensal, é
a forma usada na movimentação de pagamento da competência. Despesa única/mensal ainda pendente na
competência não tem forma de pagamento a resolver e entra num grupo "Sem forma definida".

## Tendência de 6 meses

Mostra renda x despesa total dos últimos 6 meses (incluindo o mês selecionado), no mesmo modo
(Individual/Casal) da tela. Ao contrário dos demais cards, **não é afetado pelos filtros de
despesa nem pela busca** — é uma visão histórica de totais brutos por mês, não da competência
filtrada; filtrar um mês por categoria ou forma de pagamento não faria sentido aplicado aos outros
5 meses da tendência.

## Contribuição por pessoa

Só existe em modo Casal. Duas medidas, por usuário:

- **Receita aportada**: soma da renda de cada usuário que já tem movimentação de recebimento na
  competência — só a recebida, não a agendada. Mesmo princípio de "Despesa conjunta paga".
- **Despesa conjunta paga**: soma das despesas de contexto conjunta que já têm movimentação na
  competência, agrupada por quem pagou — quem pagou deriva de
  `movimentação → forma de pagamento → conta → usuário`, mesma regra de
  [movimentacoes.md](movimentacoes.md#pagamento-de-despesa).

Este card sempre mostra os dois usuários lado a lado — não é afetado pelo filtro de pessoa descrito
abaixo, porque ele já É a quebra por pessoa.

## Filtros

O dashboard aceita os mesmos cinco filtros de despesa definidos em
[despesas.md](despesas.md#filtros): busca por descrição, categoria, tipo de
lançamento, forma de pagamento e status de pagamento, mais um filtro
exclusivo desta tela:

- **Pessoa** (só aparece em modo Casal): `ambos` (padrão) ou um dos dois usuários fixos.

Escopo dos cinco filtros de despesa — afetam, dentro do período e do modo já
selecionados: resumo (lado despesa: Despesa, Previsto e, por consequência, Realizado), Despesa por
categoria, Despesa por forma de pagamento, Individual x Conjunta e a parte de despesa das
Pendências. **Não afetam**: Renda por categoria, Contribuição por pessoa e Tendência de 6 meses —
nenhum dos três é despesa filtrável, e o segundo e o terceiro têm motivo próprio (ver as seções
acima). Não alteram a definição de Realizado/Previsto, o corte entre os dois, nem nenhuma outra
regra já descrita neste documento; apenas reduzem o conjunto de despesas considerado.

Escopo do filtro de pessoa: renda em toda a tela é restrita diretamente por dono (`usuario_id`) —
sem ambiguidade, renda sempre tem um usuário. Despesa é mais sutil: despesa conjunta **pendente**
não tem dono até ser paga (contexto conjunta é dos dois, e "quem pagou" só existe a partir da
movimentação de pagamento — mesmo princípio do card Contribuição por pessoa, ADR 0002). Por isso o
filtro de pessoa restringe, do lado despesa, só a parte **realizada** (o que aquela pessoa
efetivamente pagou, entrando em Realizado e na linha realizada da evolução do saldo) — despesa
conjunta pendente continua aparecendo para os dois usuários, em Despesa por categoria, Despesa por
forma de pagamento, Individual x Conjunta e Pendências, independentemente da pessoa selecionada.

## Questões em aberto

- **Cartão de crédito no dashboard.** Fora do escopo desta tela por ora — fatura ainda não foi
  redesenhada (mesma lacuna de [despesas.md](despesas.md) e
  [formas-pagamento.md](formas-pagamento.md)).
- **Vencimento de despesa parcelada.** Depende da fatura ser redesenhada; até lá, parcelada não
  aparece em Pendências nem na evolução diária do saldo.

---

Implementado em: `app/Domain/Financeiro/CalculadoraOcorrenciaRenda.php`,
`app/Services/Financeiro/DashboardService.php`, `app/Http/Controllers/DashboardController.php`.
