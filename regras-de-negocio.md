# Regras de negócio — Controle financeiro do casal

## Conceito do domínio

A aplicação é um sistema de controle financeiro destinado a casais.

O modelo financeiro possui dois contextos de movimentação:

### Movimentação individual

Cada usuário possui sua própria movimentação financeira.

Movimentações individuais pertencem exclusivamente ao usuário que as criou.

Um usuário não deve visualizar, consultar ou interferir nas movimentações
individuais do parceiro.

### Movimentação conjunta

O casal possui uma movimentação financeira compartilhada.

Movimentações conjuntas pertencem ao contexto do casal e são visíveis para
ambos os usuários.

### Princípio de separação

A aplicação deve manter claramente separados:

- dados financeiros individuais de cada usuário;
- dados financeiros compartilhados pelo casal.

Uma operação individual não deve ser tratada como conjunta e uma operação
conjunta não deve ser tratada como individual.

As regras específicas de renda, despesas, investimentos, categorias, saldo,
recorrência, rateio, fechamento e demais operações financeiras serão definidas
nas seções correspondentes deste documento.
