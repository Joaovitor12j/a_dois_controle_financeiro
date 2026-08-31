<?php

namespace Database\Seeders;

use App\Models\CategoriaRenda;
use Illuminate\Database\Seeder;

class CategoriaRendaSeeder extends Seeder
{
    public function run(): void
    {
        if (CategoriaRenda::query()->exists()) {
            return;
        }

        foreach ($this->categorias() as $categoria) {
            CategoriaRenda::query()->create($categoria);
        }
    }

    /** @return list<array{nome: string, cor: string, icone: string}> */
    private function categorias(): array
    {
        return [
            ['nome' => 'Salário', 'cor' => '#2F6F5E', 'icone' => 'wallet'],
            ['nome' => 'Freelance', 'cor' => '#D9A441', 'icone' => 'briefcase'],
            ['nome' => 'Investimentos', 'cor' => '#3A4B5F', 'icone' => 'trending-up'],
            ['nome' => 'Presente', 'cor' => '#7B3F55', 'icone' => 'gift'],
            ['nome' => 'Reembolso', 'cor' => '#EBCB89', 'icone' => 'rotate-ccw'],
            ['nome' => 'Outros', 'cor' => '#14202E', 'icone' => 'more-horizontal'],
        ];
    }
}
