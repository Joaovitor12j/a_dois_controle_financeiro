<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Money $limite_total
 * @property Money $limite_usado_abertura
 */
class CartaoCredito extends Model
{
    protected $table = 'cartoes_credito';

    protected $primaryKey = 'forma_pagamento_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'forma_pagamento_id',
        'limite_total',
        'limite_usado_abertura',
        'dia_fechamento',
        'dia_vencimento',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'limite_total' => MoneyCast::class,
            'limite_usado_abertura' => MoneyCast::class,
            'dia_fechamento' => 'integer',
            'dia_vencimento' => 'integer',
        ];
    }

    /** @return BelongsTo<FormaPagamento, $this> */
    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id');
    }

    /** @return HasMany<Fatura, $this> */
    public function faturas(): HasMany
    {
        return $this->hasMany(Fatura::class, 'cartao_credito_id', 'forma_pagamento_id');
    }
}
