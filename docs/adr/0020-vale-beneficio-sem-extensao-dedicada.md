# 0020. Vale e benefício sem extensão dedicada

## Contexto

A [ADR 0011](0011-vale-beneficio-como-extensao-de-forma-de-pagamento.md)
previa vale e benefício como uma extensão 1:1 de forma de pagamento (tabela
`vales_beneficio`, com `limite` e `dia_recebimento`), análoga à de cartão de
crédito, e a geração automática de uma `Renda` mensal no momento da
criação.

Esse mecanismo chegou a ser criado no banco (tabela `vales_beneficio` e
model `ValeBeneficio`), mas nunca foi conectado a nenhum fluxo de negócio:
não há relação em `FormaPagamento`, nem tratamento em
`FormaPagamentoService`, `StoreFormaPagamentoRequest` ou no formulário —
só a tabela e o model existiam, sem uso.

O que de fato foi implementado e é usado hoje é mais simples: vale e
benefício são só dois valores adicionais de `formas_pagamento.tipo`,
tratados de forma idêntica a débito, dinheiro e pix — nome, tipo e saldo
inicial opcional, sem dado próprio. A renda recebida por vale ou benefício
é lançada pelo fluxo padrão de recebimento de renda, marcando a forma de
pagamento como receptora (`recebe_renda`, [ADR 0014](0014-renda-usa-forma-de-pagamento-designada-da-conta.md)),
igual a qualquer outro tipo não-crédito.

## Decisão

Vale e benefício permanecem sem extensão própria. Não há `limite` nem
`dia_recebimento` cadastrados na forma de pagamento, e não há geração
automática de renda a partir da criação. Quem recebe vale-alimentação,
vale-transporte ou auxílio home office cadastra a renda pelo fluxo comum de
rendas, como qualquer outro recebimento.

A tabela `vales_beneficio` e o model `ValeBeneficio`, nunca usados, são
removidos.

## Consequências

- Cadastro de vale/benefício continua exatamente como está: nome, tipo e
  saldo inicial opcional — sem campos de limite ou dia de recebimento.
- Não existe sincronismo entre forma de pagamento e renda para esses
  tipos: a renda, quando existe, é uma renda comum, independente.
- `vales_beneficio` (tabela) e `ValeBeneficio` (model) são removidos.
- [docs/domain/formas-pagamento.md](../domain/formas-pagamento.md) já
  descrevia esse comportamento simplificado antes desta ADR existir; esta
  ADR só formaliza por que a arquitetura foi por esse caminho e não pelo da
  ADR 0011.

## Status

Aceita
