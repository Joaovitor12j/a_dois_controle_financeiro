<?php

namespace App\Models\Scopes;

use App\Enums\ContextoDespesa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

final class DespesaScope implements Scope
{
    /** @param Builder<covariant Model> $builder */
    public function apply(Builder $builder, Model $model): void
    {
        $usuarioId = Auth::id();

        if ($usuarioId === null) {
            $builder->whereRaw('false');

            return;
        }

        $builder->where(function (Builder $query) use ($model, $usuarioId): void {
            $query->where($model->qualifyColumn('usuario_id'), $usuarioId)
                ->orWhere($model->qualifyColumn('contexto'), ContextoDespesa::Conjunta->value);
        });
    }
}
