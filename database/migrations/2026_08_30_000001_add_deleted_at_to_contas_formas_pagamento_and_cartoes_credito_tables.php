<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('formas_pagamento', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('cartoes_credito', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('contas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('formas_pagamento', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('cartoes_credito', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
