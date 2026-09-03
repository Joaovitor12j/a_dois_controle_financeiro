<?php

namespace App\Http\Requests;

use App\Domain\Financeiro\CalculadoraCompetenciaDespesa;
use App\Domain\ValueObjects\Competencia;
use App\Models\Despesa;
use App\Models\FormaPagamento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MarcarComoPagaDespesaRequest extends FormRequest
{
    public function __construct(private readonly CalculadoraCompetenciaDespesa $calculadora)
    {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var Despesa $despesa */
        $despesa = $this->route('despesa');

        return [
            'competencia' => ['required', 'date_format:Y-m'],
            'forma_pagamento_id' => [
                'nullable',
                Rule::prohibitedIf(fn () => $despesa->ehParcelada()),
                Rule::requiredIf(fn () => ! $despesa->ehParcelada()),
                'uuid', Rule::exists('formas_pagamento', 'id')->whereNull('deleted_at'),
            ],
            'data_pagamento' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Despesa $despesa */
            $despesa = $this->route('despesa');

            if (! $validator->errors()->has('competencia')) {
                $this->validarCompetencia($validator, $despesa);
            }

            $this->validarFormaPagamento($validator, $despesa);
        });
    }

    protected function validarCompetencia(Validator $validator, Despesa $despesa): void
    {
        $competencia = Competencia::deString($this->input('competencia'));

        if (! $this->calculadora->existeNaCompetencia($despesa, $competencia)) {
            $validator->errors()->add('competencia', 'Essa despesa não tem ocorrência nessa competência.');

            return;
        }

        if ($despesa->movimentacoes()->where('competencia', $competencia->paraData())->exists()) {
            $validator->errors()->add('competencia', 'Essa competência já está paga.');
        }
    }

    protected function validarFormaPagamento(Validator $validator, Despesa $despesa): void
    {
        if ($despesa->ehParcelada()) {
            return;
        }

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
