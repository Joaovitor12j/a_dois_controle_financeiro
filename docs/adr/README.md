# Decisões de arquitetura (ADR)

Registro das decisões arquiteturais do projeto. Uma ADR documenta *por que* algo
foi decidido — não como está implementado.

Regras de negócio não entram aqui: elas vivem em [`docs/domain/`](../domain/overview.md).

## Formato

Cada ADR tem quatro seções: **Contexto**, **Decisão**, **Consequências** e
**Status**. Arquivo nomeado `NNNN-titulo-em-kebab-case.md`, numeração sequencial.

Status possíveis: `Aceita`, `Substituída por NNNN`, `Revogada`. Uma ADR aceita
não é editada para mudar de rumo — cria-se uma nova que a substitui.

## Índice

| # | Decisão | Status |
| --- | --- | --- |
| [0001](0001-auth-sobre-tabela-de-dominio.md) | Autenticação sobre a tabela de domínio `usuarios` | Aceita |
| [0002](0002-visibilidade-via-eloquent-sem-rls.md) | Visibilidade via Eloquent, não via RLS | Aceita |
| [0003](0003-uuid-via-eloquent.md) | UUID gerado pela aplicação, não pelo banco | Aceita |
| [0004](0004-postgres-real-nos-testes.md) | Testes contra Postgres real, nunca SQLite | Aceita |
| [0005](0005-value-objects-para-dinheiro.md) | Value Objects para dinheiro e conceitos de domínio | Aceita |
| [0006](0006-soft-delete-com-cascata-na-aplicacao.md) | Exclusão lógica com cascata na aplicação | Aceita |
| [0007](0007-fechamento-mensal-removido-do-dominio.md) | Fechamento mensal, acerto e sobra removidos do domínio | Aceita |
| [0008](0008-feedback-via-flash-nativo-do-inertia-e-log-so-de-excecao.md) | Feedback de ação via flash nativo do Inertia, log só de exceção não tratada | Aceita |
| [0009](0009-cartao-de-credito-como-extensao-de-forma-de-pagamento.md) | Cartão de crédito como extensão de forma de pagamento | Aceita |
| [0010](0010-visibilidade-de-despesa-contexto-individual-conjunta.md) | Visibilidade de despesa: contexto individual/conjunta | Aceita |
| [0012](0012-pagamento-de-despesa-como-movimentacao.md) | Pagamento de despesa como movimentação, não como atributo | Aceita |
