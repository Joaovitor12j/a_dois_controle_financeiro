<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('forma_pagamento_id')->constrained('formas_pagamento')->restrictOnDelete();
            $table->bigInteger('valor');
            $table->date('data');
            $table->uuid('despesa_id')->nullable();
            $table->uuid('renda_id')->nullable();
            $table->foreignUuid('fatura_id')->nullable()->constrained('faturas')->restrictOnDelete();
            $table->boolean('is_saldo_inicial')->default(false);
            $table->timestamps();

            $table->index(['forma_pagamento_id', 'data']);
        });

        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_origem_unica_check CHECK (num_nonnulls(despesa_id, renda_id, fatura_id) <= 1)');
        DB::statement('ALTER TABLE movimentacoes ADD CONSTRAINT movimentacoes_saldo_inicial_sem_origem_check CHECK (is_saldo_inicial = false OR num_nonnulls(despesa_id, renda_id, fatura_id) = 0)');
        DB::statement('CREATE UNIQUE INDEX movimentacoes_saldo_inicial_unico ON movimentacoes (forma_pagamento_id) WHERE is_saldo_inicial');
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes');
    }
};
