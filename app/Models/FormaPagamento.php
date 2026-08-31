<?php

namespace App\Models;

use App\Enums\TipoFormaPagamento;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property TipoFormaPagamento $tipo
 */
class FormaPagamento extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'formas_pagamento';

    /** @var list<string> */
    protected $fillable = [
        'conta_id',
        'nome',
        'tipo',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tipo' => TipoFormaPagamento::class,
        ];
    }

    /** @return BelongsTo<Conta, $this> */
    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    /** @return HasMany<Movimentacao, $this> */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }

    /** @return HasOne<Movimentacao, $this> */
    public function saldoInicial(): HasOne
    {
        return $this->hasOne(Movimentacao::class)->where('is_saldo_inicial', true);
    }
}
