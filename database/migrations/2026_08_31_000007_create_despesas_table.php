5<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE TYPE contexto_despesa AS ENUM ('individual', 'conjunta')");
        DB::statement("CREATE TYPE tipo_lancamento_despesa AS ENUM ('unica', 'mensal', 'parcelada')");

        Schema::create('despesas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignUuid('forma_pagamento_id')->nullable()->constrained('formas_pagamento')->restrictOnDelete();
            $table->foreignUuid('categoria_despesa_id')->constrained('categorias_despesa')->restrictOnDelete();
            $table->string('descricao');
            $table->bigInteger('valor');

            $table->date('data_vencimento')->nullable();
            $table->boolean('paga')->default(false);
            $table->date('data_pagamento')->nullable();

            $table->smallInteger('dia_vencimento')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();

            $table->smallInteger('numero_parcelas')->nullable();
            $table->date('data_primeira_parcela')->nullable();

            $table->timestamps();
        });

        DB::statement('ALTER TABLE despesas ADD COLUMN contexto contexto_despesa NOT NULL');
        DB::statement('ALTER TABLE despesas ADD COLUMN tipo_lancamento tipo_lancamento_despesa NOT NULL');

        DB::statement('ALTER TABLE despesas ADD CONSTRAINT despesas_valor_check CHECK (valor > 0)');
        DB::statement('ALTER TABLE despesas ADD CONSTRAINT despesas_dia_vencimento_check CHECK (dia_vencimento IS NULL OR dia_vencimento BETWEEN 1 AND 31)');
        DB::statement('ALTER TABLE despesas ADD CONSTRAINT despesas_numero_parcelas_check CHECK (numero_parcelas IS NULL OR numero_parcelas > 0)');

        DB::statement(<<<'SQL'
            ALTER TABLE despesas ADD CONSTRAINT despesas_campos_por_tipo_check CHECK (
                (tipo_lancamento = 'unica'
                    AND data_vencimento IS NOT NULL
                    AND dia_vencimento IS NULL AND data_inicio IS NULL AND data_fim IS NULL
                    AND numero_parcelas IS NULL AND data_primeira_parcela IS NULL)
                OR
                (tipo_lancamento = 'mensal'
                    AND data_vencimento IS NULL AND paga = FALSE AND data_pagamento IS NULL
                    AND forma_pagamento_id IS NULL
                    AND dia_vencimento IS NOT NULL AND data_inicio IS NOT NULL
                    AND numero_parcelas IS NULL AND data_primeira_parcela IS NULL)
                OR
                (tipo_lancamento = 'parcelada'
                    AND data_vencimento IS NULL AND paga = FALSE AND data_pagamento IS NULL
                    AND forma_pagamento_id IS NOT NULL
                    AND dia_vencimento IS NULL AND data_inicio IS NULL AND data_fim IS NULL
                    AND numero_parcelas IS NOT NULL AND data_primeira_parcela IS NOT NULL)
            )
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE despesas ADD CONSTRAINT despesas_pagamento_check CHECK (
                tipo_lancamento != 'unica'
                OR (paga = FALSE AND data_pagamento IS NULL AND forma_pagamento_id IS NULL)
                OR (paga = TRUE AND data_pagamento IS NOT NULL AND forma_pagamento_id IS NOT NULL)
            )
        SQL);

        Schema::table('despesas', function (Blueprint $table) {
            $table->index(['usuario_id', 'contexto'], 'despesas_usuario_contexto_idx');
            $table->index('forma_pagamento_id', 'despesas_forma_pagamento_idx');
            $table->index('categoria_despesa_id', 'despesas_categoria_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesas');

        DB::statement('DROP TYPE IF EXISTS contexto_despesa');
        DB::statement('DROP TYPE IF EXISTS tipo_lancamento_despesa');
    }
};
