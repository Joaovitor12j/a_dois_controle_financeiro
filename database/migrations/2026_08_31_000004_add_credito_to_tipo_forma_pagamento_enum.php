<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TYPE tipo_forma_pagamento ADD VALUE 'credito'");
    }

    public function down(): void
    {
        // Postgres não permite remover valor de enum.
    }
};
