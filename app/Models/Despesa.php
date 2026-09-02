<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Domain\ValueObjects\Money;
use App\Enums\ContextoDespesa;
use App\Enums\TipoLancamentoDespesa;
use App\Models\Scopes\DespesaScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Money $valor
 * @property TipoLancamentoDespesa $tipo_lancamento
 * @property ContextoDespesa $contexto
 */
#[ScopedBy(DespesaScope::class)]
class Despesa extends Model
{
    use HasUuids;

    protected $table = 'despesas';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'contexto',
        'forma_pagamento_id',
        'categoria_despesa_id',
        'descricao',
        'valor',
        'tipo_lancamento',
        'data_vencimento',
        'dia_vencimento',
        'data_inicio',
        'data_fim',
        'numero_parcelas',
        'data_primeira_parcela',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'valor' => MoneyCast::class,
            'tipo_lancamento' => TipoLancamentoDespesa::class,
            'contexto' => ContextoDespesa::class,
            'data_vencimento' => 'date',
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'data_primeira_parcela' => 'date',
        ];
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** @return BelongsTo<FormaPagamento, $this> */
    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(FormaPagamento::class);
    }

    /** @return BelongsTo<CategoriaDespesa, $this> */
    public function categoriaDespesa(): BelongsTo
    {
        return $this->belongsTo(CategoriaDespesa::class);
    }

    /** @return HasMany<Movimentacao, $this> */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class);
    }

    public function ehUnica(): bool
    {
        return $this->tipo_lancamento === TipoLancamentoDespesa::Unica;
    }

    public function ehMensal(): bool
    {
        return $this->tipo_lancamento === TipoLancamentoDespesa::Mensal;
    }

    public function ehParcelada(): bool
    {
        return $this->tipo_lancamento === TipoLancamentoDespesa::Parcelada;
    }

    public function ehConjunta(): bool
    {
        return $this->contexto === ContextoDespesa::Conjunta;
    }

    public function valorTotal(): Money
    {
        return $this->ehParcelada()
            ? $this->valor->times($this->numero_parcelas)
            : $this->valor;
    }
}
