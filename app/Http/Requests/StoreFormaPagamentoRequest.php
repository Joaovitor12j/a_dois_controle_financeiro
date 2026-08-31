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
            'saldo_inicial' => ['nullable', 'integer', 'min:0', 'prohibited_if:tipo,credito'],
            'data_saldo_inicial' => ['nullable', 'required_with:saldo_inicial', 'date'],
            'limite_total' => ['required_if:tipo,credito', 'prohibited_unless:tipo,credito', 'integer', 'min:0'],
            'limite_usado_abertura' => ['nullable', 'prohibited_unless:tipo,credito', 'integer', 'min:0'],
            'dia_fechamento' => ['required_if:tipo,credito', 'prohibited_unless:tipo,credito', 'integer', 'between:1,31'],
            'dia_vencimento' => ['required_if:tipo,credito', 'prohibited_unless:tipo,credito', 'integer', 'between:1,31'],
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
