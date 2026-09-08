<?php

namespace App\Http\Requests;

use App\Models\FormaPagamento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GerarFaturaRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'forma_pagamento_id' => ['required', 'uuid', Rule::exists('formas_pagamento', 'id')->whereNull('deleted_at')],
            'competencia' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $formaPagamentoId = $this->input('forma_pagamento_id');

            if ($formaPagamentoId === null || $validator->errors()->has('forma_pagamento_id')) {
                return;
            }

            $cartao = FormaPagamento::query()->whereKey($formaPagamentoId)->whereHas('conta')->first();

            if ($cartao === null) {
                $validator->errors()->add('forma_pagamento_id', 'A forma de pagamento selecionada é inválida.');

                return;
            }

            if (! $cartao->ehCredito()) {
                $validator->errors()->add('forma_pagamento_id', 'Só é possível gerar fatura para forma de pagamento do tipo crédito.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'forma_pagamento_id.exists' => 'A forma de pagamento selecionada é inválida.',
            'competencia.regex' => 'Informe a competência no formato AAAA-MM.',
        ];
    }
}
