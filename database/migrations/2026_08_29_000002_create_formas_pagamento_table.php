<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TYPE tipo_forma_pagamento AS ENUM ('debito', 'dinheiro', 'pix')");

        Schema::create('formas_pagamento', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->string('nome');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE formas_pagamento ADD COLUMN tipo tipo_forma_pagamento NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('formas_pagamento');

        DB::statement('DROP TYPE IF EXISTS tipo_forma_pagamento');
    }
};
