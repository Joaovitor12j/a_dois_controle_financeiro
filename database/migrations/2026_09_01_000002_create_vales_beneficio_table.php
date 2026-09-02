<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vales_beneficio', function (Blueprint $table) {
            $table->foreignUuid('forma_pagamento_id')->primary()->constrained('formas_pagamento')->cascadeOnDelete();
            $table->bigInteger('limite');
            $table->smallInteger('dia_recebimento');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE vales_beneficio ADD CONSTRAINT vales_beneficio_dia_recebimento_check CHECK (dia_recebimento BETWEEN 1 AND 31)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vales_beneficio');
    }
};
