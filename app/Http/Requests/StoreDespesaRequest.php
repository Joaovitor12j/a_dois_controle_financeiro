<?php

namespace App\Http\Requests;

use App\Enums\ContextoDespesa;
use App\Enums\TipoLancamentoDespesa;
use App\Models\FormaPagamento;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDespesaRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'contexto' => ['required', Rule::enum(ContextoDespesa::class)],
            'categoria_despesa_id' => ['required', 'uuid', Rule::exists('categorias_despesa', 'id')],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'integer', 'min:1'],
            'tipo_lancamento' => ['required', Rule::enum(TipoLancamentoDespesa::class)],

            'data_vencimento' => [
                'nullable',
                'required_if:tipo_lancamento,unica',
                'prohibited_unless:tipo_lancamento,unica',
                'date',
            ],
            'paga' => [
                'nullable',
                'required_if:tipo_lancamento,unica',
                'prohibited_unless:tipo_lancamento,unica',
                'boolean',
            ],
            'data_pagamento' => [
                'nullable',
                Rule::requiredIf(fn () => $this->boolean('paga')),
                Rule::prohibitedIf(fn () => $this->input('tipo_lancamento') !== TipoLancamentoDespesa::Unica->value
                    || ! $this->boolean('paga')),
                'date',
            ],
            'forma_pagamento_id' => [
                'nullable',
                'uuid',
                Rule::exists('formas_pagamento', 'id')->whereNull('deleted_at'),
                'prohibited_if:tipo_lancamento,mensal',
                'required_if:tipo_lancamento,parcelada',
                Rule::requiredIf(fn () => $this->input('tipo_lancamento') === TipoLancamentoDespesa::Unica->value
                    && $this->boolean('paga')),
            ],

            'dia_vencimento' => [
                'nullable',
                'required_if:tipo_lancamento,mensal',
                'prohibited_unless:tipo_lancamento,mensal',
                'integer',
                'between:1,31',
            ],
            'data_inicio' => [
                'nullable',
                'required_if:tipo_lancamento,mensal',
                'prohibited_unless:tipo_lancamento,mensal',
                'date',
                $this->regraPrimeiroDiaDoMes(),
            ],
            'data_fim' => [
                'nullable',
                'prohibited_unless:tipo_lancamento,mensal',
                'date',
                'after_or_equal:data_inicio',
            ],

            'numero_parcelas' => [
                'nullable',
                'required_if:tipo_lancamento,parcelada',
                'prohibited_unless:tipo_lancamento,parcelada',
                'integer',
                'min:1',
            ],
            'data_primeira_parcela' => [
                'nullable',
                'required_if:tipo_lancamento,parcelada',
                'prohibited_unless:tipo_lancamento,parcelada',
                'date',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validarFormaPagamento($validator, $this->input('tipo_lancamento'));
        });
    }

    protected function regraPrimeiroDiaDoMes(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value !== null && Carbon::parse($value)->day !== 1) {
                $fail('A data de início deve ser o primeiro dia do mês.');
            }
        };
    }

    protected function validarFormaPagamento(Validator $validator, ?string $tipoLancamento): void
    {
        $formaPagamentoId = $this->input('forma_pagamento_id');

        if ($formaPagamentoId === null || $validator->errors()->has('forma_pagamento_id')) {
            return;
        }

        $formaPagamento = FormaPagamento::query()->whereKey($formaPagamentoId)->whereHas('conta')->first();

        if ($formaPagamento === null) {
            $validator->errors()->add('forma_pagamento_id', 'A forma de pagamento selecionada é inválida.');

            return;
        }

        if ($tipoLancamento === TipoLancamentoDespesa::Parcelada->value && ! $formaPagamento->ehCredito()) {
            $validator->errors()->add('forma_pagamento_id', 'Despesa parcelada exige forma de pagamento do tipo crédito.');
        }
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
