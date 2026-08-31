<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE despesas DROP CONSTRAINT despesas_pagamento_check');

        DB::statement(<<<'SQL'
            ALTER TABLE despesas ADD CONSTRAINT despesas_pagamento_check CHECK (
                tipo_lancamento != 'unica'
                OR (paga = FALSE AND data_pagamento IS NULL)
                OR (paga = TRUE AND data_pagamento IS NOT NULL AND forma_pagamento_id IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE despesas DROP CONSTRAINT despesas_pagamento_check');

        DB::statement(<<<'SQL'
            ALTER TABLE despesas ADD CONSTRAINT despesas_pagamento_check CHECK (
                tipo_lancamento != 'unica'
                OR (paga = FALSE AND data_pagamento IS NULL AND forma_pagamento_id IS NULL)
                OR (paga = TRUE AND data_pagamento IS NOT NULL AND forma_pagamento_id IS NOT NULL)
            )
        SQL);
    }
};
