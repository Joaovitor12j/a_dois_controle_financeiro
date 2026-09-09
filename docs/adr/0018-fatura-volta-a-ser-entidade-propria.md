# 0018. Fatura volta a ser entidade própria

## Contexto

[ADR 0016](0016-fatura-como-quarto-tipo-de-despesa.md) unificou fatura como
o quarto valor de `despesas.tipo_lancamento`, para reaproveitar o mecanismo
de pagamento-como-movimentação já usado por única/mensal/parcelada (ver
[ADR 0012](0012-pagamento-de-despesa-como-movimentacao.md)).

Na implementação, essa unificação forçou gambiarras: parcela perdeu
pagamento próprio e passou a depender só da fatura que a cobre, exigindo
guard-clauses em `DespesaService` bloqueando `marcarComoPaga`/
`desfazerPagamento` para despesa parcelada; `categoria_despesa_id` teve que
virar nullable no banco só por causa de fatura, quebrando a obrigatoriedade
geral de categoria de despesa; e o próprio conceito de "despesa gerada, não
digitada" não se encaixava nas invariantes gerais de `Despesa`, que sempre
nasce de um formulário de mão livre.

## Decisão

Fatura volta a ser entidade própria: tabela `faturas`
(`cartao_credito_id`, `competencia`, `data_vencimento`, `valor`), com
`Movimentacao.fatura_id` como origem própria de movimentação (ao lado de
`despesa_id`/`renda_id`) e `FaturaService` dedicado para geração, recálculo,
pagamento e desfazimento de pagamento — mesma modelagem que a ADR 0016 havia
aposentado.

## Consequências

- `TipoLancamentoDespesa` volta a ter só `Unica`, `Mensal` e `Parcelada` —
  não existe mais o caso `Fatura`.
- `DespesaService` mantém guard-clauses bloqueando pagamento avulso de
  parcela: parcela continua sem pagamento próprio, mas agora porque é a
  `Fatura` (entidade separada) que agrega o pagamento, não porque parcela
  "é" um tipo de fatura.
- `categoria_despesa_id` volta a ser obrigatória para todo `tipo_lancamento`
  de despesa — a exceção de fatura não existe mais nesse nível, porque
  fatura não é despesa.
- Migrations `2026_09_08_000005_remover_fatura_de_despesas.php` (reverte
  000002/000003) e `2026_09_08_000006_recriar_faturas_e_fatura_id_em_
  movimentacoes.php` (recria a tabela `faturas` e a coluna `fatura_id`)
  implementam a reversão.
- [`docs/domain/fatura.md`](../domain/fatura.md) é reescrito para refletir a
  entidade própria.

## Status

Aceita
