# Regras de negócio — Controle financeiro do casal

## Entidades

- `Usuario`
- `Conta` — vinculada a um usuário. Campo puramente informativo.
- `Renda` — por usuário, por mês. Pode ter múltiplas fontes no mesmo mês.
- `Categoria` — `{ nome, tipo: individual | conjunta, usuario_id? }`. Escopo fechado: uma categoria "Lazer individual" nunca aparece em listagens de despesa conjunta, e vice-versa.
- `Despesa` — `{ valor, categoria_id, tipo: individual | conjunta, subtipo: fixa | variavel, pago_por, conta_id, data, recorrente }`
- `InvestimentoConjunto` — valor fixo mensal.
- `InvestimentoIndividual` — `{ usuario_id, valor, descricao, data }`. Debita do saldo individual.
- `SaldoIndividual` — ledger de lançamentos por usuário. **Nunca é um campo mutável** — é sempre a soma de todos os lançamentos.

## Regra central: `pago_por` e `conta_id` são metadados

Em toda `Despesa`, os campos `conta_id` e `pago_por` são **puramente históricos** e não entram em nenhum cálculo. Servem só para consulta ("de qual conta saiu esse gasto", "quem pagou").

> Nota: a versão anterior desta regra usava `pago_por` para apurar acerto de contas entre os dois usuários. Essa funcionalidade (acerto de despesas conjuntas, divisão de sobra, fechamento mensal) foi removida deste documento — a implementação estava incorreta e será redesenhada do zero como uma nova funcionalidade, com suas próprias regras.

## Renda

Cada usuário informa sua renda mensal, podendo ter mais de uma fonte no mesmo mês.

- `fixa: boolean` (default false). Renda fixa segue o mesmo modelo de recorrência
  de Despesa: origem + cópias independentes por mês, materialização lazy e
  idempotente no acesso ao mês, dentro da mesma transação.
- `renda_origem_id` (nullable, FK self-reference) identifica cópias.
- `recorrente_ate` (nullable) na origem — mesma semântica de encerramento de despesa.
- `materializarRecorrencias` cobre despesas E rendas na mesma chamada (mesmo gatilho:
  acesso ao mês).
- Cópia de renda fixa herda sempre o `valor`/`fonte` da ORIGEM na materialização —
  nunca de uma cópia editada de mês anterior.

## Edição e exclusão de renda

- Renda variável (fixa=false): editar/excluir afeta só aquele registro.
- Renda fixa, editando/excluindo a ORIGEM (mês de criação): só existe a opção
  "esta e futuras" — equivale a editar o valor da origem, que propaga para as
  próximas materializações. Meses já materializados antes da edição não são
  afetados retroativamente.
- Renda fixa, editando/excluindo uma CÓPIA (mês != criação):
  - "Somente este mês": edita/exclui só a cópia. Origem e demais cópias intactas.
  - "Esta e futuras": atualiza o valor da origem (propaga para meses ainda não
    materializados) + atualiza em lote as cópias já materializadas com
    mes_referencia >= mes da cópia editada. Para exclusão: seta recorrente_ate
    da origem para o mês anterior ao da cópia + exclui em lote as cópias
    materializadas >= esse mês.
  - Cópias de meses anteriores ao editado nunca são tocadas.

## Despesas

- Tipo `conjunta`: do casal. Tipo `individual`: de uma pessoa só.
- Subtipo `fixa` (recorrente, repete todo mês) ou `variavel`.
- Despesa recorrente gera lançamento automático nos meses seguintes (ver seção Recorrência).
- Despesa individual debita diretamente do `SaldoIndividual` do usuário dono.

## Recorrência

Mesmo modelo template + cópias usado por `Despesa` (recorrente) e `Renda` (fixa) — ver seção
Renda para os campos equivalentes de `Renda`.

- **Modelo template + cópias**: a despesa criada como recorrente é a **origem**. Cada mês subsequente recebe um registro próprio de `Despesa`, com `recorrencia_origem_id` apontando para a origem. Cópias são independentes — valor, categoria e metadados podem ser ajustados em um mês sem afetar os demais nem a origem.
- **Materialização lazy e idempotente**: as cópias de um mês são geradas quando o mês é acessado — nunca por job agendado. A materialização verifica existência antes de criar (uma cópia por origem por mês, no máximo).
- **Encerramento explícito**: a origem tem `recorrente_ate` (nullable). Nulo = repete indefinidamente. Encerrar define o último mês de vigência; cópias já materializadas permanecem intactas, meses posteriores não geram cópia.
- Cópia materializada de despesa individual gera o `debito_despesa` no `SaldoIndividual` no ato da materialização, como qualquer despesa individual.

## Edição e exclusão de despesa

- Campos editáveis: só `nome`, `valor` e `data` — categoria, tipo, subtipo, conta e
  pagador não mudam numa edição (exclui e relança se precisar).
- Em despesa recorrente (origem ou cópia), a `data` só pode mudar **dentro do mesmo
  mês** — preserva o invariante "uma cópia por origem por mês" da materialização.
  Despesa avulsa muda a data livremente.
- Despesa avulsa: editar/excluir afeta só aquele registro (exclusão é física).
- Despesa recorrente, editando/excluindo a ORIGEM: edita direto (propaga para os
  meses ainda não materializados; cópias já materializadas ficam intactas). Excluir
  a origem apaga também **todas** as cópias e seus débitos no ledger.
- Despesa recorrente, editando/excluindo uma CÓPIA (exige escopo):
  - "Somente este mês": edita só a cópia. Exclusão é **definitiva** via soft-delete
    (`excluida = true`): a linha permanece no banco para o dedupe da materialização
    não recriar a cópia; toda listagem/agregação filtra `excluida = false`.
  - "Esta e futuras": edição atualiza a origem + as cópias com data >= mês da cópia,
    projetando o **dia** da nova data no mês de cada linha (a data da despesa é o
    próprio agrupador mensal). Exclusão seta `recorrente_ate` da origem para o mês
    anterior + apaga fisicamente as cópias >= esse mês (a origem encerrada já
    impede a rematerialização).
  - Cópias de meses anteriores nunca são tocadas.
- Despesa individual sempre sincroniza (edição: valor E data) ou apaga (exclusão) o
  lançamento `debito_despesa` correspondente no `SaldoIndividual`, na mesma transação.

## Saldo individual

É um extrato, não um valor. Cada lançamento tem `{ usuario_id, tipo: debito_despesa | debito_investimento, valor, referencia_id, data }`. O saldo atual é a soma de todos os lançamentos do usuário — **acumula mês a mês, nunca reseta**. Com esse saldo, o usuário pode lançar despesas individuais e investimentos individuais, que geram débitos.

**Saldo negativo é permitido.** Despesas e investimentos individuais podem ser lançados mesmo acima do saldo disponível — nenhuma validação de bloqueio. Quando o saldo atual for negativo, a UI o exibe normalmente com sinal e `--negativo`, sem estado de erro ou alerta.

> Nota: os tipos de lançamento `credito_sobra`, `debito_acerto` e `credito_acerto` faziam parte do fechamento mensal removido. Voltarão quando a nova funcionalidade de acerto/sobra for desenhada.

## Visibilidade

Usuário autenticado só enxerga:
- Tudo que é `tipo = conjunta` (despesas, categorias)
- Suas próprias `despesa`/`investimento` `tipo = individual`

Nunca vê despesas ou investimentos individuais do parceiro. Regra aplicada via
Global Scopes do Eloquent (`VisivelParaUsuarioScope`, `DonoScope`).
`RecorrenciaMaterializador` processa despesas/rendas dos dois usuários
independente de quem está logado, exigindo `withoutGlobalScope(...)` explícito —
ponto de atenção: bypassar o scope errado quebra o isolamento.

## Categorias

Escopo fechado por `tipo` (individual | conjunta). Uma categoria de nome igual em escopos diferentes são registros distintos — nunca aparecem misturados em relatório/listagem.

## Subcategorias (tags)

Refinamento opcional de uma `Categoria`, relação N:N com lançamentos via tabelas de junção (`despesa_tags`, `renda_tags`, ...) — uma despesa/renda pode ter 0..N tags.

- Tag não tem cor nem ícone próprios: herda visualmente a cor da categoria pai, sempre em variação mais sutil que o badge de categoria (outline com opacidade reduzida, sem preenchimento, sem ícone) — reforça que é um refinamento, não uma identidade própria.
- Seleção de tags na criação/edição de lançamento é sempre opcional (0..N) e restrita às tags da categoria escolhida.
