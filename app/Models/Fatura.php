<?php

namespace App\Models;

use App\Casts\CompetenciaCast;
use App\Casts\MoneyCast;
use App\Domain\ValueObjects\Competencia;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property Competencia $competencia
 * @property Money $valor
 */
class Fatura extends Model
{
    use HasUuids;

    protected $table = 'faturas';

    /** @var list<string> */
    protected $fillable = [
        'cartao_credito_id',
        'competencia',
        'data_vencimento',
        'valor',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'competencia' => CompetenciaCast::class,
            'data_vencimento' => 'date',
            'valor' => MoneyCast::class,
        ];
    }

    /** @return BelongsTo<CartaoCredito, $this> */
    public function cartaoCredito(): BelongsTo
    {
        return $this->belongsTo(CartaoCredito::class, 'cartao_credito_id', 'forma_pagamento_id');
    }

    /** @return HasMany<Movimentacao, $this> */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }

    /** @return HasOne<Movimentacao, $this> */
    public function movimentacaoDePagamento(): HasOne
    {
        return $this->hasOne(Movimentacao::class)->whereNull('despesa_id');
    }

    public function estaPaga(): bool
    {
        return $this->movimentacaoDePagamento()->exists();
    }
}
