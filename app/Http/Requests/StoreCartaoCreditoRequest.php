<?php

namespace App\Http\Requests;

use App\Models\Conta;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCartaoCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'conta_id' => [
                'required',
                'uuid',
                Rule::exists('contas', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! Conta::query()->whereKey($value)->exists()) {
                        $fail('A conta selecionada é inválida.');
                    }
                },
            ],
            'nome' => ['required', 'string', 'max:255'],
            'limite_total' => ['required', 'integer', 'min:0'],
            'limite_usado_abertura' => ['nullable', 'integer', 'min:0'],
            'dia_fechamento' => ['required', 'integer', 'between:1,31'],
            'dia_vencimento' => ['required', 'integer', 'between:1,31'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'conta_id.exists' => 'A conta selecionada é inválida.',
        ];
    }
}
