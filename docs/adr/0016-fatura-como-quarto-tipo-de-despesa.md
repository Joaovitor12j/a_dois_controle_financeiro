# 0016. Fatura como quarto tipo de despesa

## Contexto

Um ciclo anterior do projeto havia modelado `Fatura` como entidade própria
(tabela `faturas` com `cartao_credito_id` + `competencia`) e
`Movimentacao.fatura_id` como uma terceira origem possível de movimentação,
ao lado de `despesa_id` e `renda_id`. Essa infraestrutura chegou a ser
criada no banco (ver [ADR 0009](0009-cartao-de-credito-como-extensao-de-forma-de-pagamento.md))
mas nunca foi conectada a nenhum fluxo de negócio: não havia Service,
Controller, Policy nem rota para `Fatura` — só o model e a tabela existiam.

Ao redesenhar o domínio de fatura, `CalculadoraCompetenciaDespesa::vencimento()`
já continha uma pista da direção pretendida: para despesa parcelada, lançava
`LogicException` com a mensagem "o dado pertence à fatura" — sinalizando que
o vencimento de uma cobrança em cartão não é um dado da parcela em si, é um
dado da fatura que a cobre.

## Decisão

Fatura não é uma entidade separada. É o quarto valor de
`despesas.tipo_lancamento` (ao lado de única, mensal e parcelada), sujeita
ao mesmo mecanismo de pagamento-como-movimentação já usado para os outros
três tipos (ver [ADR 0012](0012-pagamento-de-despesa-como-movimentacao.md)):
uma competência, uma `data_vencimento`, uma movimentação de pagamento.

Isso aposenta a infraestrutura do ciclo anterior: tabela `faturas`, model
`Fatura`, `CartaoCredito::faturas()` e `Movimentacao.fatura_id` são
removidos. Não existe mais uma terceira origem de movimentação — pagamento
de fatura usa `despesa_id`, como pagamento de qualquer outra despesa.

O valor de uma fatura nunca é digitado: é calculado na geração, agregando
por data as movimentações em crédito daquele cartão dentro da janela de
fechamento correspondente à competência pedida — sem precisar de uma tabela
de vínculo entre movimentação e fatura. Ver [fatura.md](../domain/fatura.md)
para a regra de fechamento e agregação completas.

Fatura é o único tipo de despesa isento de categoria obrigatória — ela não é
um gasto próprio, é o agregado de gastos que já têm categoria
individualmente (a parcela, a única, a mensal).

Gerenciamento de fatura (gerar, regenerar, ver, marcar como paga) ganha uma
aba própria no frontend, separada da tela genérica de Despesas — decisão de
usabilidade, já que fatura não nasce de formulário livre. A tela de Despesas
passa a excluir fatura da listagem; a criação continua impossível pelo
formulário genérico (`tipo_lancamento` aceito nele continua restrito a
única/mensal/parcelada).

## Consequências

- `TipoLancamentoDespesa` ganha o caso `Fatura`; `despesas_campos_por_tipo_check`
  ganha um quarto ramo; `categoria_despesa_id` vira nullable no banco.
- `CalculadoraCompetenciaDespesa` passa a tratar fatura como única
  (competência derivada de `data_vencimento`, vencimento = a própria data).
- Nova classe de domínio `CalculadoraFatura` concentra o cálculo de janela
  de fechamento e data de vencimento a partir de `dia_fechamento`/
  `dia_vencimento` do cartão — conceito que não pertence a
  `CalculadoraCompetenciaDespesa` porque não é sobre competência de despesa.
- `DespesaService::marcarComoPaga`/`desfazerPagamento`/`excluir` funcionam
  para fatura sem nenhuma alteração, por já serem genéricos sobre `Despesa`.
- `faturas`, `Fatura`, `CartaoCredito::faturas()` e `Movimentacao.fatura_id`
  são removidos; o teste que cobria esses relacionamentos
  (`CastsFinanceiroTest`) foi ajustado para parar em `CartaoCredito`.

## Status

Aceita
