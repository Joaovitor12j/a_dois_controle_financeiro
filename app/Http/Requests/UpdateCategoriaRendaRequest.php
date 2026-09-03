<?php

namespace App\Http\Requests;

use App\Enums\IconeCategoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaRendaRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias_renda', 'nome')->ignore($this->route('categoriaRenda')),
            ],
            'cor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icone' => ['required', Rule::enum(IconeCategoria::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe uma categoria de renda com esse nome.',
            'cor.regex' => 'A cor deve ser um código hexadecimal válido (ex.: #2F6F5E).',
        ];
    }
}
