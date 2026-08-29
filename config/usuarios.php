<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usuários iniciais
    |--------------------------------------------------------------------------
    |
    | O app não tem cadastro público: são dois usuários fixos, criados pelo
    | seeder. As credenciais vivem no .env para não versionar senha real.
    |
    | Estes valores são apenas SEMENTE INICIAL. O seeder só age quando a tabela
    | está vazia — depois disso a fonte de verdade é o banco, editado pelo
    | painel. Mudar algo aqui não altera usuário já criado.
    |
    */

    'iniciais' => [
        [
            'nome' => env('USUARIO_1_NOME', 'Usuário Um'),
            'email' => env('USUARIO_1_EMAIL', 'usuario.um@exemplo.test'),
            'senha' => env('USUARIO_1_SENHA', 'senha-de-desenvolvimento'),
            'cor' => env('USUARIO_1_COR', '#2F6F5E'),
        ],
        [
            'nome' => env('USUARIO_2_NOME', 'Usuário Dois'),
            'email' => env('USUARIO_2_EMAIL', 'usuario.dois@exemplo.test'),
            'senha' => env('USUARIO_2_SENHA', 'senha-de-desenvolvimento'),
            'cor' => env('USUARIO_2_COR', '#7B3F55'),
        ],
    ],

];
