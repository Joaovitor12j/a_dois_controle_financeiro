<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TYPE tipo_lancamento_despesa ADD VALUE 'fatura'");
    }

    public function down(): void
    {
        // Postgres não permite remover valor de enum.
    }
};
