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
            $table->foreignUuid('cartao_credito_id')->constrained('cartoes_credito')->cascadeOnDelete();
            $table->date('competencia');
            $table->timestamps();

            $table->unique(['cartao_credito_id', 'competencia']);
        });

        DB::statement('ALTER TABLE faturas ADD CONSTRAINT faturas_competencia_dia_1_check CHECK (EXTRACT(DAY FROM competencia) = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('faturas');
    }
};
