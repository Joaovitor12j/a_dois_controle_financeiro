<?php

namespace App\Models;

use App\Domain\ValueObjects\Money;
use App\Enums\TipoFormaPagamento;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'recebe_renda',
    ];

    /** @var list<string> */
    protected $appends = ['saldo'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tipo' => TipoFormaPagamento::class,
            'recebe_renda' => 'boolean',
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

    /** @return HasOne<CartaoCredito, $this> */
    public function cartaoCredito(): HasOne
    {
        return $this->hasOne(CartaoCredito::class, 'forma_pagamento_id');
    }

    public function ehCredito(): bool
    {
        return $this->tipo === TipoFormaPagamento::Credito;
    }

    /** @return Attribute<Money|null, never> */
    protected function saldo(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->ehCredito() || ! array_key_exists('movimentacoes_sum_valor', $this->attributes)) {
                    return null;
                }

                return Money::fromCents((int) $this->attributes['movimentacoes_sum_valor']);
            },
        );
    }
}
