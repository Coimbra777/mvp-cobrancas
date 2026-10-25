<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Guarda só os dígitos: "(85) 99999-0000" → "85999990000".
        $this->merge(['telefone' => preg_replace('/\D/', '', (string) $this->input('telefone'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['required', 'regex:/^\d{10,11}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome.',
            'nome.max' => 'O nome pode ter até 255 caracteres.',
            'telefone.required' => 'Informe o telefone.',
            'telefone.regex' => 'Informe o celular com DDD (10 ou 11 dígitos).',
        ];
    }
}
