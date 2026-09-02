<?php

namespace App\Http\Requests;

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
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Despesa $despesa */
            $despesa = $this->route('despesa');

            if (! $despesa->ehUnica()) {
                $validator->errors()->add('tipo_lancamento', 'Só despesa única tem pagamento a desfazer.');
            }

            if (! $despesa->paga) {
                $validator->errors()->add('paga', 'Despesa não está paga.');
            }
        });
    }
}
