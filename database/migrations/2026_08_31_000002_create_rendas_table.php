<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TYPE tipo_recorrencia_renda AS ENUM ('unica', 'mensal')");

        Schema::create('rendas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignUuid('conta_id')->constrained('contas')->restrictOnDelete();
            $table->foreignUuid('categoria_renda_id')->constrained('categorias_renda')->restrictOnDelete();
            $table->string('descricao');
            $table->bigInteger('valor');
            $table->date('data_recebimento')->nullable();
            $table->smallInteger('dia_recebimento')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE rendas ADD COLUMN tipo_recorrencia tipo_recorrencia_renda NOT NULL');

        DB::statement(<<<'SQL'
            ALTER TABLE rendas ADD CONSTRAINT rendas_recorrencia_datas_check CHECK (
                (tipo_recorrencia = 'unica' AND data_recebimento IS NOT NULL AND dia_recebimento IS NULL AND data_inicio IS NULL AND data_fim IS NULL)
                OR
                (tipo_recorrencia = 'mensal' AND data_recebimento IS NULL AND dia_recebimento IS NOT NULL AND data_inicio IS NOT NULL)
            )
        SQL);

        DB::statement('ALTER TABLE rendas ADD CONSTRAINT rendas_dia_recebimento_check CHECK (dia_recebimento IS NULL OR dia_recebimento BETWEEN 1 AND 31)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rendas');

        DB::statement('DROP TYPE IF EXISTS tipo_recorrencia_renda');
    }
};
