<?php

namespace App\Models;

use App\Casts\CompetenciaCast;
use App\Casts\MoneyCast;
use App\Domain\ValueObjects\Competencia;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Money $valor
 * @property Competencia|null $competencia
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
        'competencia',
        'fatura_id',
        'is_saldo_inicial',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'valor' => MoneyCast::class,
            'data' => 'date',
            'competencia' => CompetenciaCast::class,
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

    /** @return BelongsTo<Despesa, $this> */
    public function despesa(): BelongsTo
    {
        return $this->belongsTo(Despesa::class);
    }
}
