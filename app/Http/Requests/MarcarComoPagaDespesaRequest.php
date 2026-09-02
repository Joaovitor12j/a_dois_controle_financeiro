<?php

namespace App\Http\Requests;

use App\Models\Despesa;
use App\Models\FormaPagamento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MarcarComoPagaDespesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            /** @var Despesa $despesa */
            $despesa = $this->route('despesa');

            if (! $despesa->ehUnica()) {
                $validator->errors()->add('tipo_lancamento', 'Só despesa única pode ser marcada como paga.');
            }

            if ($despesa->paga) {
                $validator->errors()->add('paga', 'Despesa já está marcada como paga.');
            }

            $this->validarFormaPagamento($validator);
        });
    }

    protected function validarFormaPagamento(Validator $validator): void
    {
        $formaPagamentoId = $this->input('forma_pagamento_id');

        if ($formaPagamentoId === null || $validator->errors()->has('forma_pagamento_id')) {
            return;
        }

        $existe = FormaPagamento::query()->whereKey($formaPagamentoId)->whereHas('conta')->exists();

        if (! $existe) {
            $validator->errors()->add('forma_pagamento_id', 'A forma de pagamento selecionada é inválida.');
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'forma_pagamento_id.exists' => 'A forma de pagamento selecionada é inválida.',
        ];
    }
}
