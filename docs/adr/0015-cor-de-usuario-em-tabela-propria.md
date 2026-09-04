# 0015. Cor de usuário e cor do casal em tabela própria

## Contexto

A cor de identidade vivia como coluna direta em `usuarios.cor`. Com a
introdução da cor do casal — derivada do par de cores dos dois usuários — não
havia onde persistir esse terceiro valor sem forçar uma leitura conjunta dos
dois usuários a cada uso (cabeçalho, dashboard, tela de perfil), recalculando
a cada request.

## Decisão

Cor de usuário e cor do casal passam a viver em uma tabela própria,
`cores_usuario`, em relação 1:1 com `usuarios` (chave primária = chave
estrangeira, mesmo padrão de extensão usado em `vales_beneficio` e
`cartoes_credito` — ver [ADR 0009](0009-cartao-de-credito-como-extensao-de-forma-de-pagamento.md)).

Cada linha carrega a cor do próprio usuário e a cor do casal vigente — esta
última replicada nas duas linhas, sempre igual entre elas. A cor do casal é
recalculada e regravada nas duas linhas sempre que qualquer um dos dois
usuários troca sua própria cor (ver regra de cálculo em
[usuarios.md](../domain/usuarios.md)), nunca escolhida diretamente.

`usuarios.cor` é removida. A migration que cria `cores_usuario` também migra
o valor existente de `usuarios.cor` para a nova tabela antes de remover a
coluna.

## Consequências

- Leitura da cor do casal deixa de exigir buscar os dois usuários e recalcular
  a cada request — é uma coluna já persistida.
- Toda leitura de cor de usuário (header, dashboard, perfil) passa a depender
  da relação `usuario->corUsuario`, não mais de um atributo direto do model
  `Usuario`.
- Escrita de cor passa a ser responsabilidade de um serviço dedicado
  (`CorUsuarioService`), que atualiza a cor do usuário e, quando há parceiro,
  recalcula e regrava a cor do casal nas duas linhas — a rota de perfil não
  grava mais cor diretamente no model `Usuario`.
- Toda criação de usuário (seeder, factory, e qualquer fluxo futuro) precisa
  criar também a linha correspondente em `cores_usuario`; um usuário sem essa
  linha é um estado inválido, não tratado defensivamente pelo código.

## Status

Aceita
