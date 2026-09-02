<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->date('competencia')->nullable();
        });

        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_competencia_dia_1_check CHECK (competencia IS NULL OR EXTRACT(DAY FROM competencia) = 1)');
        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_competencia_despesa_check CHECK (despesa_id IS NULL OR competencia IS NOT NULL)');
        DB::statement('CREATE UNIQUE INDEX movimentacoes_despesa_competencia_unico ON movimentacoes (despesa_id, competencia) WHERE despesa_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX movimentacoes_despesa_competencia_unico');
        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_competencia_despesa_check');
        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_competencia_dia_1_check');

        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->dropColumn('competencia');
        });
    }
};
