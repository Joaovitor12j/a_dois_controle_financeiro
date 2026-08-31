# 0010. Visibilidade de despesa: contexto individual/conjunta

## Contexto

O [overview.md](../domain/overview.md) tinha como questão em aberto como o
contexto conjunto do casal se materializa financeiramente. Despesa é o
primeiro domínio a precisar resolver isso — [contas.md](../domain/contas.md)
já havia fechado que conta é sempre individual, mas isso não respondia por
onde uma despesa do casal é registrada.

## Decisão

Despesa carrega um campo `contexto`, com dois valores: `individual` ou
`conjunta`.

- Despesa **individual** é visível, editável e excluível apenas por quem a
  criou — mesma regra de posse de [rendas.md](../domain/rendas.md).
- Despesa **conjunta** é visível, editável e excluível pelos dois usuários,
  independentemente de quem a criou.

Essa regra não é binária dono/conjunto-vazio, então não reaproveita
`DonoScope` como está. É tratada como uma extensão da
[ADR 0002](0002-visibilidade-via-eloquent-sem-rls.md), não como violação
dela: a visibilidade continua resolvida via Eloquent (Global Scope +
Policy), mas o scope de despesa precisa considerar `contexto` além de
`usuario_id`.

Conta continua sempre individual — essa decisão não muda. O contexto
conjunto se materializa na despesa (e nos demais lançamentos que vierem a
adotar o mesmo campo), não na conta nem por rateio entre contas
individuais.

## Consequências

- Despesa precisa de um Global Scope próprio, distinto de `DonoScope`, que
  libere registros de `contexto = 'conjunta'` para os dois usuários e
  restrinja registros de `contexto = 'individual'` ao dono.
- A Policy de despesa autoriza edição/exclusão considerando `contexto`, não
  apenas posse — despesa conjunta de outro usuário não é 403, é operável.
- A questão em aberto do `overview.md` sobre materialização do contexto
  conjunto fica respondida para despesa; outros domínios que vierem a
  precisar de contexto conjunto (ex.: futura movimentação) podem seguir o
  mesmo mecanismo.

## Status

Aceita.
