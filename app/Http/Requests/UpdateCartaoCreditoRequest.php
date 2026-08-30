<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartaoCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cartao_credito'));
    }

    /** @return array<string, ValidationRule|array|string> */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'limite_total' => ['required', 'integer', 'min:0'],
            'dia_fechamento' => ['required', 'integer', 'between:1,31'],
            'dia_vencimento' => ['required', 'integer', 'between:1,31'],
        ];
    }
}
