# 0019. Interpretação determinística da mensagem do assistente

## Contexto

O assistente ([assistente.md](../domain/assistente.md)) recebe a pergunta do
usuário como texto livre. O conjunto de perguntas que ele responde, porém, é
fechado, e o vocabulário que aparece nelas também é controlado: meses,
categorias do próprio usuário, contexto individual ou conjunto. Isso não
exige interpretação de linguagem aberta.

## Decisão

A mensagem é interpretada por regra determinística no backend, que produz
uma intenção validada contra listas fechadas antes de qualquer consulta.
Modelo de linguagem fica fora do caminho no MVP.

Alternativas descartadas:

- **Modelo de linguagem gerando consulta ao banco**: contorna o
  `DonoScope` e a [ADR 0002](0002-visibilidade-via-eloquent-sem-rls.md), com
  risco de vazamento entre os dois usuários.
- **Modelo de linguagem interpretando a mensagem e devolvendo intenção
  estruturada**: viável, mas acrescenta serviço externo, latência por turno
  e teste não determinístico para resolver um vocabulário que já é fechado.
- **Modelo de linguagem redigindo a resposta**: o número passaria por um
  componente que pode reescrevê-lo.

## Consequências

- A interpretação é testável sem dependência externa.
- Perguntas fora do vocabulário falham de forma visível, e essa falha fica
  registrada no histórico.
- Um modelo de linguagem pode ser acrescentado depois como alternativa de
  interpretação, desde que respeite a mesma fronteira: produzir intenção
  validada, nunca consultar dado nem produzir número.

## Status

Aceita.
