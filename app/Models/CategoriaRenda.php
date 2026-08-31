<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaRenda extends Model
{
    use HasUuids;

    protected $table = 'categorias_renda';

    /** @var list<string> */
    protected $fillable = [
        'nome',
        'cor',
        'icone',
    ];

    /** @return HasMany<Renda, $this> */
    public function rendas(): HasMany
    {
        return $this->hasMany(Renda::class);
    }
}
