<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaDespesa extends Model
{
    use HasUuids;

    protected $table = 'categorias_despesa';

    /** @var list<string> */
    protected $fillable = [
        'nome',
        'cor',
        'icone',
    ];

    /** @return HasMany<Despesa, $this> */
    public function despesas(): HasMany
    {
        return $this->hasMany(Despesa::class);
    }
}
