<?php

namespace App\Http\Requests;

use App\Domain\Financeiro\CalculadoraOcorrenciaRenda;
use App\Domain\ValueObjects\Competencia;
use App\Models\Renda;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MarcarComoRecebidaRendaRequest extends FormRequest
{
    public function __construct(private readonly CalculadoraOcorrenciaRenda $calculadora)
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
        /** @var Renda $renda */
        $renda = $this->route('renda');
        $elegiveis = $renda->formasPagamentoElegiveisParaRecebimento();

        return [
            'competencia' => ['required', 'date_format:Y-m'],
            'forma_pagamento_id' => [
                'nullable',
                Rule::prohibitedIf($elegiveis->count() <= 1),
                Rule::requiredIf($elegiveis->count() > 1),
                'uuid', Rule::in($elegiveis->pluck('id')->all()),
            ],
            'data_recebimento' => ['required', 'date'],
            'valor' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Renda $renda */
            $renda = $this->route('renda');

            if ($renda->formasPagamentoElegiveisParaRecebimento()->isEmpty()) {
                $validator->errors()->add('forma_pagamento_id', 'Nenhuma forma de pagamento da conta recebe renda. Configure uma antes.');

                return;
            }

            if (! $validator->errors()->has('competencia')) {
                $this->validarCompetencia($validator, $renda);
            }
        });
    }

    protected function validarCompetencia(Validator $validator, Renda $renda): void
    {
        $competencia = Competencia::deString($this->input('competencia'));

        if (! $this->calculadora->existeNaCompetencia($renda, $competencia)) {
            $validator->errors()->add('competencia', 'Essa renda não tem ocorrência nessa competência.');

            return;
        }

        if ($renda->movimentacoes()->where('competencia', $competencia->paraData())->exists()) {
            $validator->errors()->add('competencia', 'Essa competência já está recebida.');
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'forma_pagamento_id.in' => 'A forma de pagamento selecionada é inválida.',
        ];
    }
}
