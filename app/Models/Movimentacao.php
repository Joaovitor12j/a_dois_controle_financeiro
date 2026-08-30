<?php

namespace App\Models;

use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Money $valor
 */
class Movimentacao extends Model
{
    use HasUuids;

    protected $table = 'movimentacoes';

    /** @var list<string> */
    protected $fillable = [
        'forma_pagamento_id',
        'valor',
        'data',
        'despesa_id',
        'renda_id',
        'fatura_id',
        'is_saldo_inicial',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'valor' => Money::class,
            'data' => 'date',
            'is_saldo_inicial' => 'boolean',
        ];
    }

    /** @return BelongsTo<FormaPagamento, $this> */
    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class);
    }

    /** @return BelongsTo<Fatura, $this> */
    public function fatura(): BelongsTo
    {
        return $this->belongsTo(Fatura::class);
    }
}
