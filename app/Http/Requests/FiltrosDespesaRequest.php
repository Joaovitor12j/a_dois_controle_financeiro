<?php

namespace App\Http\Requests;

use App\Enums\FiltroStatusPagamento;
use App\Enums\TipoLancamentoDespesa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrosDespesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'categoria_despesa_id' => ['sometimes', 'uuid', Rule::exists('categorias_despesa', 'id')],
            'tipo' => ['sometimes', Rule::enum(TipoLancamentoDespesa::class)],
            'forma_pagamento_id' => ['sometimes', 'uuid', Rule::exists('formas_pagamento', 'id')->whereNull('deleted_at')],
            'status' => ['sometimes', Rule::enum(FiltroStatusPagamento::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'categoria_despesa_id.exists' => 'A categoria selecionada é inválida.',
            'forma_pagamento_id.exists' => 'A forma de pagamento selecionada é inválida.',
        ];
    }
}
