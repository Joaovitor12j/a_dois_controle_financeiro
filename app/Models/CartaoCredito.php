<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Money $limite_total
 * @property Money $limite_usado_abertura
 */
class CartaoCredito extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'cartoes_credito';

    /** @var list<string> */
    protected $fillable = [
        'conta_id',
        'nome',
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

    /** @return BelongsTo<Conta, $this> */
    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    /** @return HasMany<Fatura, $this> */
    public function faturas(): HasMany
    {
        return $this->hasMany(Fatura::class);
    }
}
