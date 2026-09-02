<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE despesas ADD CONSTRAINT despesas_data_inicio_primeiro_dia_check CHECK (
                data_inicio IS NULL OR EXTRACT(DAY FROM data_inicio) = 1
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE despesas DROP CONSTRAINT despesas_data_inicio_primeiro_dia_check');
    }
};
