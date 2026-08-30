<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    use HasUuids;

    protected $table = 'contas';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'nome',
    ];

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

    /** @return HasMany<CartaoCredito, $this> */
    public function cartoesCredito(): HasMany
    {
        return $this->hasMany(CartaoCredito::class);
    }
}
