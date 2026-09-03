<?php

namespace App\Http\Requests;

use App\Enums\IconeCategoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaDespesaRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias_despesa', 'nome')->ignore($this->route('categoriaDespesa')),
            ],
            'cor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icone' => ['required', Rule::enum(IconeCategoria::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe uma categoria de despesa com esse nome.',
            'cor.regex' => 'A cor deve ser um código hexadecimal válido (ex.: #2F6F5E).',
        ];
    }
}
