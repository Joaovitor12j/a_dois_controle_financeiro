<?php

namespace App\Models;

use App\Domain\ValueObjects\Money;
use App\Models\Scopes\DonoScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy(DonoScope::class)]
class Conta extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'contas';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'nome',
    ];

    /** @var list<string> */
    protected $appends = ['logo_url', 'saldo_total'];

    /** @return Attribute<string, never> */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => route('logos.show', $this->nome),
        );
    }

    /** @return Attribute<Money|null, never> */
    protected function saldoTotal(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->relationLoaded('formasPagamento')) {
                    return null;
                }

                return $this->formasPagamento
                    ->reject(fn (FormaPagamento $formaPagamento) => $formaPagamento->ehCredito())
                    ->reduce(
                        fn (Money $acumulado, FormaPagamento $formaPagamento) => $acumulado->plus($formaPagamento->saldo ?? Money::zero()),
                        Money::zero(),
                    );
            },
        );
    }

    protected static function booted(): void
    {
        static::deleted(function (Conta $conta): void {
            if ($conta->isForceDeleting()) {
                return;
            }

            $conta->formasPagamento()->delete();
        });
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** @return HasMany<FormaPagamento, $this> */
    public function formasPagamento(): HasMany
    {
        return $this->hasMany(FormaPagamento::class);
    }
}
