<?php

namespace App\Http\Requests;

use App\Models\FormaPagamento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFormaPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('formaPagamento'));
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var FormaPagamento $formaPagamento */
        $formaPagamento = $this->route('formaPagamento');
        $ehCredito = $formaPagamento->ehCredito() ? 'required' : 'prohibited';

        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['prohibited'],
            'limite_total' => [$ehCredito, 'nullable', 'integer', 'min:0'],
            'dia_fechamento' => [$ehCredito, 'nullable', 'integer', 'between:1,31'],
            'dia_vencimento' => [$ehCredito, 'nullable', 'integer', 'between:1,31'],
        ];
    }
}
