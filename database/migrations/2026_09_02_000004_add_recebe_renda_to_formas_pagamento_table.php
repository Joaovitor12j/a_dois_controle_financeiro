<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formas_pagamento', function (Blueprint $table) {
            $table->boolean('recebe_renda')->default(false);
        });

        DB::statement('ALTER TABLE formas_pagamento ADD CONSTRAINT formas_pagamento_recebe_renda_credito_check CHECK (NOT recebe_renda OR tipo != \'credito\')');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE formas_pagamento DROP CONSTRAINT formas_pagamento_recebe_renda_credito_check');

        Schema::table('formas_pagamento', function (Blueprint $table) {
            $table->dropColumn('recebe_renda');
        });
    }
};
