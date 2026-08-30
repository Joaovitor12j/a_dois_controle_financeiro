# 0001 — Autenticação sobre a tabela de domínio `usuarios`

## Contexto

O projeto tem dois usuários fixos, semeados. Não há cadastro aberto, recuperação
de conta por auto-serviço nem crescimento da base. Manter uma tabela `users` do
Breeze separada da entidade de domínio criaria duas representações da mesma
pessoa e uma tradução constante entre elas.

## Decisão

A entidade de domínio é a entidade autenticável: `Usuario extends Authenticatable`
apontando para a tabela `usuarios`.

Não há self-signup. Registro, recuperação de senha e verificação de e-mail foram
removidos do scaffolding do Breeze.

## Consequências

- Não existe tabela `users`; toda referência a "usuário" no domínio e na
  autenticação é o mesmo registro.
- Criar usuário é operação de seed, não de aplicação.
- Se um dia houver necessidade de convidar usuários, esta decisão precisa ser
  revista — não é uma limitação a contornar com gambiarra.

## Status

Aceita.
