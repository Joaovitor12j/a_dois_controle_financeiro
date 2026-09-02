<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE despesas DROP CONSTRAINT despesas_pagamento_check');
        DB::statement('ALTER TABLE despesas DROP CONSTRAINT despesas_campos_por_tipo_check');

        Schema::table('despesas', function (Blueprint $table) {
            $table->dropColumn(['paga', 'data_pagamento']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE despesas ADD CONSTRAINT despesas_campos_por_tipo_check CHECK (
                (tipo_lancamento = 'unica'
                    AND data_vencimento IS NOT NULL
                    AND forma_pagamento_id IS NULL
                    AND dia_vencimento IS NULL AND data_inicio IS NULL AND data_fim IS NULL
                    AND numero_parcelas IS NULL AND data_primeira_parcela IS NULL)
                OR
                (tipo_lancamento = 'mensal'
                    AND data_vencimento IS NULL
                    AND forma_pagamento_id IS NULL
                    AND dia_vencimento IS NOT NULL AND data_inicio IS NOT NULL
                    AND numero_parcelas IS NULL AND data_primeira_parcela IS NULL)
                OR
                (tipo_lancamento = 'parcelada'
                    AND data_vencimento IS NULL
                    AND forma_pagamento_id IS NOT NULL
                    AND dia_vencimento IS NULL AND data_inicio IS NULL AND data_fim IS NULL
                    AND numero_parcelas IS NOT NULL AND data_primeira_parcela IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE despesas DROP CONSTRAINT despesas_campos_por_tipo_check');

        Schema::table('despesas', function (Blueprint $table) {
            $table->boolean('paga')->default(false);
            $table->date('data_pagamento')->nullable();
        });

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
                OR (paga = FALSE AND data_pagamento IS NULL)
                OR (paga = TRUE AND data_pagamento IS NOT NULL AND forma_pagamento_id IS NOT NULL)
            )
        SQL);
    }
};
