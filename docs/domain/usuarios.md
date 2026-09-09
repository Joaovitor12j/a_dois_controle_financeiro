# Usuários

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

Cada um dos dois usuários fixos do sistema tem uma cor de identidade, usada
para diferenciá-lo visualmente do parceiro em qualquer lugar do sistema que
represente autoria ou pertencimento — por exemplo cabeçalho e gráficos do
painel.

## Cor de identidade

A cor de identidade é escolhida a partir de uma paleta fechada de oito
opções pré-definidas, não de um seletor livre:

| Cor | Hex |
| --- | --- |
| Azul | `#2563EB` |
| Verde | `#16A34A` |
| Roxo | `#7C3AED` |
| Laranja | `#EA580C` |
| Rosa | `#DB2777` |
| Ciano | `#0891B2` |
| Âmbar | `#D97706` |
| Índigo | `#4F46E5` |

Uma opção só é selecionável se, ao mesmo tempo:

- for diferente da cor atual do parceiro;
- for harmônica com a cor atual do parceiro — cada opção da paleta tem um
  conjunto fixo de opções com as quais é harmônica, e a cor do parceiro
  precisa estar nesse conjunto. Duas cores deixam de ser harmônicas quando
  são próximas demais entre si (mesma família de cor), o que hoje só
  acontece nestes pares: Azul/Ciano, Azul/Índigo, Roxo/Índigo e
  Laranja/Âmbar.

Alterar a própria cor não altera a cor do parceiro.

Enquanto o sistema não tiver os dois usuários criados, a restrição acima não
se aplica — não há cor de parceiro para comparar.

Se a cor atual do parceiro não for nenhuma das oito da paleta (caso de um
valor herdado de antes desta regra existir), a harmonia não pode ser
avaliada — só a restrição de ser diferente da cor do parceiro se aplica.

## Cor do casal

O par formado pela cor de um usuário e pela cor do outro determina uma
terceira cor, usada para representar dados do contexto conjunto (ver
"Contextos de movimentação" em [overview.md](overview.md)).

A cor do casal é o ponto médio entre as duas cores individuais — a média de
cada componente (vermelho, verde, azul) do par vigente. Não é escolhida
diretamente por nenhum dos usuários, e muda automaticamente se qualquer um
dos dois trocar sua cor.

## Questões em aberto

Nenhuma no momento.
