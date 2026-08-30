<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

final class DonoScope implements Scope
{
    /** @param Builder<covariant Model> $builder */
    public function apply(Builder $builder, Model $model): void
    {
        $usuarioId = Auth::id();

        if ($usuarioId === null) {
            $builder->whereRaw('false');

            return;
        }

        $builder->where($model->qualifyColumn('usuario_id'), $usuarioId);
    }
}
