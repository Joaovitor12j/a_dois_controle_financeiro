<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_competencia_despesa_check');
        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_competencia_origem_check CHECK ((despesa_id IS NULL AND renda_id IS NULL) OR competencia IS NOT NULL)');
        DB::statement('CREATE UNIQUE INDEX movimentacoes_renda_competencia_unico ON movimentacoes (renda_id, competencia) WHERE renda_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX movimentacoes_renda_competencia_unico');
        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_competencia_origem_check');
        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_competencia_despesa_check CHECK (despesa_id IS NULL OR competencia IS NOT NULL)');
    }
};
