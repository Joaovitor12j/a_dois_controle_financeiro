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
            if ($validator->errors()->has('competencia')) {
                return;
            }

            /** @var Despesa $despesa */
            $despesa = $this->route('despesa');
            $competencia = Competencia::deString($this->input('competencia'));

            if (! $despesa->movimentacoes()->where('competencia', $competencia->paraData())->exists()) {
                $validator->errors()->add('competencia', 'Essa competência não está paga.');
            }
        });
    }
}
