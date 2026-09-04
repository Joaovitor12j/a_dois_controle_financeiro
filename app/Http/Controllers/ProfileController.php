<?php

namespace App\Http\Controllers;

use App\Domain\Usuario\CorIdentidade;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Usuario;
use App\Services\Usuario\CorUsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $usuario = $request->user();
        $parceiro = Usuario::where('id', '!=', $usuario->id)->first();

        return Inertia::render('Profile/Edit', [
            'status' => session('status'),
            'corAtual' => $usuario->corUsuario->cor,
            'corParceiro' => $parceiro?->corUsuario->cor,
            'paleta' => array_map(fn (CorIdentidade $opcao): array => [
                'id' => $opcao->name,
                'nome' => $opcao->nome(),
                'hex' => $opcao->value,
                'compativeis' => array_map(fn (CorIdentidade $c): string => $c->value, $opcao->compativeis()),
            ], CorIdentidade::cases()),
            'corDoCasal' => $usuario->corUsuario->cor_casal,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, CorUsuarioService $corUsuarioService): RedirectResponse
    {
        $validado = $request->validated();
        $usuario = $request->user();

        $usuario->fill(collect($validado)->only(['nome', 'email'])->all());
        $usuario->save();

        if (array_key_exists('cor', $validado)) {
            $corUsuarioService->atualizar($usuario, $validado['cor']);
        }

        return Redirect::route('profile.edit');
    }
}
