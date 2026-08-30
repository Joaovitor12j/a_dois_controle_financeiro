# 0006 — Exclusão lógica com cascata na aplicação

## Contexto

Registros financeiros são referenciados por histórico. Exclusão física apagaria
o rastro e quebraria referências. Ao mesmo tempo, excluir um agrupador precisa
levar junto o que ele contém: um meio de pagamento não deve sobreviver à conta
que o contém.

A cascata do banco (`ON DELETE CASCADE`) atua apenas na exclusão física — ela não
enxerga exclusão lógica, porque para o banco a linha continua lá.

## Decisão

A exclusão feita pela aplicação é lógica.

A cascata da exclusão lógica é implementada na aplicação, num hook do model pai,
que exclui logicamente os filhos. O hook não age quando a exclusão é definitiva —
nesse caso a cascata do banco já resolve.

A cascata do banco é mantida como rede de segurança para exclusão física.

## Consequências

- Duas cascatas coexistem, cada uma no seu cenário: lógica na aplicação, física
  no banco.
- Um filho excluído por arrasto não sabe que foi arrastado; restaurar o pai não
  o restaura automaticamente. Se restauração vier a existir, isso precisa ser
  decidido explicitamente.
- Excluir o pai fora do Eloquent não dispara a cascata lógica.

## Status

Aceita.
