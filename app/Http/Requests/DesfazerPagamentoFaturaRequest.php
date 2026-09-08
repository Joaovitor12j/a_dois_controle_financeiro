<?php

namespace App\Http\Requests;

use App\Models\Fatura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DesfazerPagamentoFaturaRequest extends FormRequest
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Fatura $fatura */
            $fatura = $this->route('fatura');

            if (! $fatura->estaPaga()) {
                $validator->errors()->add('fatura', 'Essa fatura não está paga.');
            }
        });
    }
}
