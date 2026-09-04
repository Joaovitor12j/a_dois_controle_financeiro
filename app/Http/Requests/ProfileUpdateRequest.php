<?php

namespace App\Http\Requests;

use App\Domain\Usuario\CorIdentidade;
use App\Models\Usuario;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $parceiro = Usuario::where('id', '!=', $this->user()->id)->first();
        $corParceiroAtual = $parceiro?->corUsuario?->cor;

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(Usuario::class)->ignore($this->user()->id),
            ],
            'cor' => [
                'sometimes',
                'string',
                Rule::enum(CorIdentidade::class),
                function (string $attribute, mixed $value, Closure $fail) use ($corParceiroAtual): void {
                    if ($corParceiroAtual === null) {
                        return;
                    }

                    if ($value === $corParceiroAtual) {
                        $fail('Esta cor já é usada pelo seu parceiro.');

                        return;
                    }

                    $corParceiro = CorIdentidade::tryFrom($corParceiroAtual);
                    $cor = CorIdentidade::from($value);

                    if ($corParceiro !== null && ! in_array($corParceiro, $cor->compativeis(), true)) {
                        $fail('Esta cor não combina com a cor do seu parceiro.');
                    }
                },
            ],
        ];
    }
}
