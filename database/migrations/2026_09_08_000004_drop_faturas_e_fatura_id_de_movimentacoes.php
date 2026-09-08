<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_origem_unica_check');
        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_saldo_inicial_sem_origem_check');

        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->dropForeign(['fatura_id']);
            $table->dropColumn('fatura_id');
        });

        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_origem_unica_check CHECK (num_nonnulls(despesa_id, renda_id) <= 1)');
        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_saldo_inicial_sem_origem_check CHECK (is_saldo_inicial = false OR num_nonnulls(despesa_id, renda_id) = 0)');

        Schema::dropIfExists('faturas');
    }

    public function down(): void
    {
        Schema::create('faturas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cartao_credito_id');
            $table->date('competencia');
            $table->timestamps();

            $table->unique(['cartao_credito_id', 'competencia']);
            $table->foreign('cartao_credito_id')->references('forma_pagamento_id')->on('cartoes_credito')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE faturas ADD CONSTRAINT faturas_competencia_dia_1_check CHECK (EXTRACT(DAY FROM competencia) = 1)');

        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_origem_unica_check');
        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_saldo_inicial_sem_origem_check');

        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->foreignUuid('fatura_id')->nullable()->constrained('faturas')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_origem_unica_check CHECK (num_nonnulls(despesa_id, renda_id, fatura_id) <= 1)');
        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_saldo_inicial_sem_origem_check CHECK (is_saldo_inicial = false OR num_nonnulls(despesa_id, renda_id, fatura_id) = 0)');
    }
};
