<?php

namespace Database\Seeders;

use App\Models\CategoriaDespesa;
use Illuminate\Database\Seeder;

class CategoriaDespesaSeeder extends Seeder
{
    public function run(): void
    {
        if (CategoriaDespesa::query()->exists()) {
            return;
        }

        foreach ($this->categorias() as $categoria) {
            CategoriaDespesa::query()->create($categoria);
        }
    }

    /** @return list<array{nome: string, cor: string, icone: string}> */
    private function categorias(): array
    {
        return [
            ['nome' => 'Moradia', 'cor' => '#2F6F5E', 'icone' => 'home'],
            ['nome' => 'Alimentação', 'cor' => '#D9A441', 'icone' => 'utensils'],
            ['nome' => 'Transporte', 'cor' => '#3A6EA5', 'icone' => 'car'],
            ['nome' => 'Saúde', 'cor' => '#C1447E', 'icone' => 'heart-pulse'],
            ['nome' => 'Educação', 'cor' => '#4E7BA6', 'icone' => 'graduation-cap'],
            ['nome' => 'Lazer', 'cor' => '#E08E45', 'icone' => 'popcorn'],
            ['nome' => 'Assinaturas', 'cor' => '#6C5B9E', 'icone' => 'repeat'],
            ['nome' => 'Compras', 'cor' => '#B5548C', 'icone' => 'shopping-bag'],
            ['nome' => 'Pets', 'cor' => '#7FA650', 'icone' => 'dog'],
            ['nome' => 'Viagem', 'cor' => '#2E9CB0', 'icone' => 'plane'],
            ['nome' => 'Academia', 'cor' => '#D6564B', 'icone' => 'dumbbell'],
            ['nome' => 'Contas de casa', 'cor' => '#5C7C8A', 'icone' => 'zap'],
            ['nome' => 'Vestuário', 'cor' => '#9C6FBF', 'icone' => 'shirt'],
            ['nome' => 'Cuidados pessoais', 'cor' => '#E07A9B', 'icone' => 'sparkles'],
            ['nome' => 'Presentes', 'cor' => '#7B3F55', 'icone' => 'gift'],
            ['nome' => 'Impostos e taxas', 'cor' => '#4A4A4A', 'icone' => 'receipt'],
            ['nome' => 'Seguros', 'cor' => '#35618E', 'icone' => 'shield'],
            ['nome' => 'Doações', 'cor' => '#3F8F6E', 'icone' => 'hand-heart'],
            ['nome' => 'Outros', 'cor' => '#14202E', 'icone' => 'more-horizontal'],
        ];
    }
}
