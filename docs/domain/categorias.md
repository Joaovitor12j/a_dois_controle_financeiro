# Categorias

Conceitos gerais do domínio estão em [overview.md](overview.md) e não são
repetidos aqui.

## Conceito

Categoria de renda e categoria de despesa classificam, respectivamente, uma
renda ([rendas.md](rendas.md#categoria)) e uma despesa
([despesas.md](despesas.md#categoria)). São duas entidades distintas, uma por
tabela, sem relação entre si — uma categoria de renda nunca classifica uma
despesa, e vice-versa.

## Propriedade e visibilidade

Categoria não tem dono. É uma entidade compartilhada entre os dois usuários:
qualquer usuário autenticado pode ver, criar, editar e excluir qualquer
categoria, de renda ou de despesa, independentemente de quem a criou.

## Identificação

Uma categoria é identificada por um nome, obrigatório e único dentro do seu
tipo — não pode existir duas categorias de renda com o mesmo nome, nem duas
categorias de despesa com o mesmo nome. O mesmo nome pode existir uma vez em
categoria de renda e uma vez em categoria de despesa, sem conflito, por
serem entidades distintas.

## Aparência

Toda categoria tem uma cor, obrigatória, e um ícone, obrigatório. O ícone não
é texto livre: é restrito a um catálogo fixo, compartilhado entre categoria
de renda e categoria de despesa. Cor e ícone servem só para identificação
visual da categoria nas telas do sistema; não têm regra de negócio própria.

## Exclusão

Uma categoria em uso — com pelo menos uma renda ou despesa vinculada — não
pode ser excluída. A tentativa é bloqueada pela aplicação antes de chegar ao
banco, com uma mensagem de erro visível ao usuário explicando o motivo. Não
existe exclusão em cascata para o vínculo entre categoria e renda/despesa:
para excluir a categoria, as rendas ou despesas que a usam precisam deixar
de usá-la antes.

## Questões em aberto

Nenhuma no momento.

---

Implementado em: `app/Models/CategoriaRenda.php`, `app/Models/CategoriaDespesa.php`,
`app/Enums/IconeCategoria.php`,
`app/Services/Financeiro/CategoriaRendaService.php`,
`app/Services/Financeiro/CategoriaDespesaService.php`,
`app/Policies/CategoriaRendaPolicy.php`, `app/Policies/CategoriaDespesaPolicy.php`,
`app/Http/Controllers/CategoriaController.php`,
`app/Http/Controllers/CategoriaRendaController.php`,
`app/Http/Controllers/CategoriaDespesaController.php`,
`resources/js/Pages/Categorias/Index.tsx`,
`database/migrations/2026_08_31_000001_create_categorias_renda_table.php`,
`database/migrations/2026_08_31_000006_create_categorias_despesa_table.php`,
`database/migrations/2026_09_03_000001_add_unique_nome_to_categorias_renda_table.php`,
`database/migrations/2026_09_03_000002_add_unique_nome_to_categorias_despesa_table.php`.
