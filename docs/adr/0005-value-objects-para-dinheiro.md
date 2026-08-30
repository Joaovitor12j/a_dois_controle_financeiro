# 0005 — Value Objects para dinheiro e conceitos de domínio

## Contexto

Valor monetário representado como `float` acumula erro de arredondamento; como
`int` solto, perde a unidade e permite somar centavos com reais ou com um
percentual sem que nada acuse o erro. O mesmo vale para outros conceitos com
regra própria, como período de referência.

## Decisão

Conceitos de domínio que não são primitivos crus são modelados como Value
Objects, em `app/Domain/ValueObjects/`.

Um Value Object é imutável e valida suas invariantes no construtor — um valor
inválido não chega a existir.

Dinheiro é sempre `Money`. Nunca um `float`, nunca um `int` solto representando
valor monetário.

## Consequências

- Operação inválida entre conceitos distintos falha na construção, não silenciosamente.
- Persistência e serialização exigem conversão explícita nas bordas.
- Value Objects são testados isoladamente, sem framework: invariantes e imutabilidade.

## Status

Aceita.
