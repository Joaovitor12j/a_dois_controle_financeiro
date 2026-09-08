<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faturas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cartao_credito_id')->constrained('cartoes_credito', 'forma_pagamento_id')->cascadeOnDelete();
            $table->date('competencia');
            $table->date('data_vencimento');
            $table->bigInteger('valor');
            $table->timestamps();

            $table->unique(['cartao_credito_id', 'competencia']);
        });

        DB::statement('ALTER TABLE faturas ADD CONSTRAINT faturas_competencia_dia_1_check CHECK (EXTRACT(DAY FROM competencia) = 1)');
        DB::statement('ALTER TABLE faturas ADD CONSTRAINT faturas_valor_check CHECK (valor > 0)');

        Schema::table('movimentacoes', function (Blueprint $table) {
            $table->foreignUuid('fatura_id')->nullable()->constrained('faturas')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_origem_unica_check');
        DB::statement('ALTER TABLE movimentacoes DROP CONSTRAINT movimentacoes_saldo_inicial_sem_origem_check');

        // fatura_id pode coexistir com despesa_id (movimentação de parcela criada pelo
        // pagamento em cascata da fatura). Só é exclusivo com renda_id.
        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_origem_unica_check CHECK (renda_id IS NULL OR (despesa_id IS NULL AND fatura_id IS NULL))');
        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_saldo_inicial_sem_origem_check CHECK (is_saldo_inicial = false OR (despesa_id IS NULL AND renda_id IS NULL AND fatura_id IS NULL))');

        // Uma única "movimentação própria da fatura" (despesa_id nulo) por fatura.
        DB::statement('CREATE UNIQUE INDEX movimentacoes_fatura_pagamento_unico ON movimentacoes (fatura_id) WHERE fatura_id IS NOT NULL AND despesa_id IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX movimentacoes_fatura_pagamento_unico');
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
};
