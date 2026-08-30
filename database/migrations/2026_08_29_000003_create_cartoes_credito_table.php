<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartoes_credito', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->string('nome');
            $table->bigInteger('limite_total');
            $table->bigInteger('limite_usado_abertura')->default(0);
            $table->smallInteger('dia_fechamento');
            $table->smallInteger('dia_vencimento');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE cartoes_credito ADD CONSTRAINT cartoes_credito_dia_fechamento_check CHECK (dia_fechamento BETWEEN 1 AND 31)');
        DB::statement('ALTER TABLE cartoes_credito ADD CONSTRAINT cartoes_credito_dia_vencimento_check CHECK (dia_vencimento BETWEEN 1 AND 31)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cartoes_credito');
    }
};
