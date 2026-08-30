<?php

namespace App\Models;

use App\Casts\CompetenciaCast;
use App\Domain\ValueObjects\Competencia;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Competencia $competencia
 */
class Fatura extends Model
{
    use HasUuids;

    protected $table = 'faturas';

    /** @var list<string> */
    protected $fillable = [
        'cartao_credito_id',
        'competencia',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'competencia' => CompetenciaCast::class,
        ];
    }

    /** @return BelongsTo<CartaoCredito, $this> */
    public function cartaoCredito(): BelongsTo
    {
        return $this->belongsTo(CartaoCredito::class);
    }

    /** @return HasMany<Movimentacao, $this> */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }
}
