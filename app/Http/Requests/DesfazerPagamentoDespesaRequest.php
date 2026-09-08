<?php

namespace App\Http\Requests;

use App\Domain\ValueObjects\Competencia;
use App\Models\Despesa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DesfazerPagamentoDespesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'competencia' => ['required', 'date_format:Y-m'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Despesa $despesa */
            $despesa = $this->route('despesa');

            if ($despesa->ehParcelada()) {
                $validator->errors()->add('despesa', 'Parcela não tem pagamento próprio — desfaça o pagamento da fatura que a cobre.');

                return;
            }

            if ($validator->errors()->has('competencia')) {
                return;
            }

            $competencia = Competencia::deString($this->input('competencia'));

            if (! $despesa->movimentacoes()->where('competencia', $competencia->paraData())->exists()) {
                $validator->errors()->add('competencia', 'Essa competência não está paga.');
            }
        });
    }
}
