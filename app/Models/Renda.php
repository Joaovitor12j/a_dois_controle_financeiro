<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Domain\ValueObjects\Money;
use App\Enums\TipoRecorrencia;
use App\Models\Scopes\DonoScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Money $valor
 * @property TipoRecorrencia $tipo_recorrencia
 */
#[ScopedBy(DonoScope::class)]
class Renda extends Model
{
    use HasUuids;

    protected $table = 'rendas';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'conta_id',
        'categoria_renda_id',
        'descricao',
        'valor',
        'tipo_recorrencia',
        'data_recebimento',
        'dia_recebimento',
        'data_inicio',
        'data_fim',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'valor' => MoneyCast::class,
            'tipo_recorrencia' => TipoRecorrencia::class,
            'data_recebimento' => 'date',
            'data_inicio' => 'date',
            'data_fim' => 'date',
        ];
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** @return BelongsTo<Conta, $this> */
    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    /** @return BelongsTo<CategoriaRenda, $this> */
    public function categoriaRenda(): BelongsTo
    {
        return $this->belongsTo(CategoriaRenda::class);
    }

    /** @return HasMany<Movimentacao, $this> */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }
}
