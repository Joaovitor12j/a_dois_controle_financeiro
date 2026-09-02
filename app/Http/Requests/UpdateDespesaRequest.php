<?php

namespace App\Http\Requests;

use App\Enums\ContextoDespesa;
use App\Enums\TipoLancamentoDespesa;
use App\Models\Despesa;
use App\Models\FormaPagamento;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDespesaRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var Despesa $despesa */
        $despesa = $this->route('despesa');
        $tipoLancamento = $despesa->tipo_lancamento;

        $ehUnica = $tipoLancamento === TipoLancamentoDespesa::Unica ? 'required' : 'prohibited';
        $ehMensal = $tipoLancamento === TipoLancamentoDespesa::Mensal ? 'required' : 'prohibited';
        $ehParcelada = $tipoLancamento === TipoLancamentoDespesa::Parcelada ? 'required' : 'prohibited';

        return [
            'contexto' => ['required', Rule::enum(ContextoDespesa::class)],
            'categoria_despesa_id' => ['required', 'uuid', Rule::exists('categorias_despesa', 'id')],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'integer', 'min:1'],
            'tipo_lancamento' => ['prohibited'],

            'data_vencimento' => [$ehUnica, 'date'],
            'forma_pagamento_id' => [
                'nullable', 'uuid', Rule::exists('formas_pagamento', 'id')->whereNull('deleted_at'),
                $tipoLancamento === TipoLancamentoDespesa::Parcelada ? 'required' : 'prohibited',
            ],

            'dia_vencimento' => [$ehMensal, 'integer', 'between:1,31'],
            'data_inicio' => [$ehMensal, 'date', $this->regraPrimeiroDiaDoMes()],
            'data_fim' => [
                $tipoLancamento === TipoLancamentoDespesa::Mensal ? 'nullable' : 'prohibited',
                'date', 'after_or_equal:data_inicio',
            ],

            'numero_parcelas' => [$ehParcelada, 'integer', 'min:1'],
            'data_primeira_parcela' => [$ehParcelada, 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Despesa $despesa */
            $despesa = $this->route('despesa');

            $this->validarFormaPagamento($validator, $despesa->tipo_lancamento);
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

    protected function validarFormaPagamento(Validator $validator, TipoLancamentoDespesa $tipoLancamento): void
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

        if ($tipoLancamento === TipoLancamentoDespesa::Parcelada && ! $formaPagamento->ehCredito()) {
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
