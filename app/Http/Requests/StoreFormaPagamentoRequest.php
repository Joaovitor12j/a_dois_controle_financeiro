<?php

namespace App\Http\Requests;

use App\Enums\TipoFormaPagamento;
use App\Models\Conta;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormaPagamentoRequest extends FormRequest
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
            'tipo' => ['required', Rule::enum(TipoFormaPagamento::class)],
            'saldo_inicial' => ['nullable', 'integer', 'min:0'],
            'data_saldo_inicial' => ['required_with:saldo_inicial', 'date'],
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
