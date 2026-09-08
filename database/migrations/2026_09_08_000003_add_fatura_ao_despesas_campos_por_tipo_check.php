<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE despesas DROP CONSTRAINT despesas_campos_por_tipo_check');
        DB::statement('ALTER TABLE despesas ALTER COLUMN categoria_despesa_id DROP NOT NULL');

        DB::statement(<<<'SQL'
            ALTER TABLE despesas ADD CONSTRAINT despesas_campos_por_tipo_check CHECK (
                (tipo_lancamento = 'unica'
                    AND categoria_despesa_id IS NOT NULL
                    AND data_vencimento IS NOT NULL
                    AND forma_pagamento_id IS NULL
                    AND dia_vencimento IS NULL AND data_inicio IS NULL AND data_fim IS NULL
                    AND numero_parcelas IS NULL AND data_primeira_parcela IS NULL)
                OR
                (tipo_lancamento = 'mensal'
                    AND categoria_despesa_id IS NOT NULL
                    AND data_vencimento IS NULL
                    AND forma_pagamento_id IS NULL
                    AND dia_vencimento IS NOT NULL AND data_inicio IS NOT NULL
                    AND numero_parcelas IS NULL AND data_primeira_parcela IS NULL)
                OR
                (tipo_lancamento = 'parcelada'
                    AND categoria_despesa_id IS NOT NULL
                    AND data_vencimento IS NULL
                    AND forma_pagamento_id IS NOT NULL
                    AND dia_vencimento IS NULL AND data_inicio IS NULL AND data_fim IS NULL
                    AND numero_parcelas IS NOT NULL AND data_primeira_parcela IS NOT NULL)
                OR
                (tipo_lancamento = 'fatura'
                    AND categoria_despesa_id IS NULL
                    AND data_vencimento IS NOT NULL
                    AND forma_pagamento_id IS NOT NULL
                    AND dia_vencimento IS NULL AND data_inicio IS NULL AND data_fim IS NULL
                    AND numero_parcelas IS NULL AND data_primeira_parcela IS NULL)
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE despesas DROP CONSTRAINT despesas_campos_por_tipo_check');
        DB::statement("DELETE FROM despesas WHERE tipo_lancamento = 'fatura'");
        DB::statement('ALTER TABLE despesas ALTER COLUMN categoria_despesa_id SET NOT NULL');

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
};
