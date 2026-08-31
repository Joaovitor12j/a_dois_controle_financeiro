<?php

namespace App\Http\Requests;

use App\Enums\TipoRecorrencia;
use App\Models\Conta;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRendaRequest extends FormRequest
{
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
            'categoria_renda_id' => ['required', 'uuid', Rule::exists('categorias_renda', 'id')],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'integer', 'min:1'],
            'tipo_recorrencia' => ['required', Rule::enum(TipoRecorrencia::class)],
            'data_recebimento' => [
                'required_if:tipo_recorrencia,unica',
                'prohibited_if:tipo_recorrencia,mensal',
                'date',
            ],
            'dia_recebimento' => [
                'required_if:tipo_recorrencia,mensal',
                'prohibited_if:tipo_recorrencia,unica',
                'integer',
                'between:1,31',
            ],
            'data_inicio' => [
                'required_if:tipo_recorrencia,mensal',
                'prohibited_if:tipo_recorrencia,unica',
                'date',
            ],
            'data_fim' => [
                'nullable',
                'prohibited_if:tipo_recorrencia,unica',
                'date',
                'after_or_equal:data_inicio',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'conta_id.exists' => 'A conta selecionada é inválida.',
            'categoria_renda_id.exists' => 'A categoria selecionada é inválida.',
        ];
    }
}
