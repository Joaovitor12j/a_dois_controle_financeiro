<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Money $limite
 */
class ValeBeneficio extends Model
{
    protected $table = 'vales_beneficio';

    protected $primaryKey = 'forma_pagamento_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'forma_pagamento_id',
        'limite',
        'dia_recebimento',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'limite' => MoneyCast::class,
            'dia_recebimento' => 'integer',
        ];
    }

    /** @return BelongsTo<FormaPagamento, $this> */
    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id');
    }
}
