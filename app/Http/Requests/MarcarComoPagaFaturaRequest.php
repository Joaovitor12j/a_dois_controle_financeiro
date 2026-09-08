<?php

namespace App\Http\Requests;

use App\Models\Fatura;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MarcarComoPagaFaturaRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'forma_pagamento_id' => ['required', 'uuid', Rule::exists('formas_pagamento', 'id')->whereNull('deleted_at')],
            'data_pagamento' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Fatura $fatura */
            $fatura = $this->route('fatura');

            if ($fatura->estaPaga()) {
                $validator->errors()->add('fatura', 'Essa fatura já está paga.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'forma_pagamento_id.exists' => 'A forma de pagamento selecionada é inválida.',
        ];
    }
}
