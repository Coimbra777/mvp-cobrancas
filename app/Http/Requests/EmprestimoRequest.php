<?php

namespace App\Http\Requests;

use App\Enums\MetodoCalculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmprestimoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'valor' => self::normalizarDecimal($this->input('valor')),
            'taxa_mensal' => self::normalizarDecimal($this->input('taxa_mensal')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'valor' => ['required', 'regex:/^\d{1,9}(\.\d{1,2})?$/', function (string $campo, mixed $valor, \Closure $falha) {
                if ((float) $valor <= 0) {
                    $falha('O valor precisa ser maior que zero.');
                }
            }],
            'taxa_mensal' => ['required', 'regex:/^\d{1,3}(\.\d{1,2})?$/', 'numeric', 'between:0,100'],
            'metodo' => ['required', Rule::enum(MetodoCalculo::class)],
            'quantidade_parcelas' => ['required', 'integer', 'between:1,60'],
            'primeiro_vencimento' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Escolha o cliente.',
            'cliente_id.exists' => 'Cliente não encontrado.',
            'valor.required' => 'Informe o valor.',
            'valor.regex' => 'Informe o valor em reais, ex.: 1.500,00.',
            'taxa_mensal.required' => 'Informe a taxa mensal.',
            'taxa_mensal.regex' => 'Informe a taxa em %, com até 2 casas, ex.: 2,5.',
            'taxa_mensal.numeric' => 'Informe a taxa em %, com até 2 casas, ex.: 2,5.',
            'taxa_mensal.between' => 'A taxa precisa estar entre 0 e 100%.',
            'metodo.required' => 'Escolha o método de cálculo.',
            'metodo.enum' => 'Escolha juros simples ou compostos.',
            'quantidade_parcelas.required' => 'Informe o número de parcelas.',
            'quantidade_parcelas.integer' => 'Informe um número inteiro de parcelas.',
            'quantidade_parcelas.between' => 'O número de parcelas vai de 1 a 60.',
            'primeiro_vencimento.required' => 'Informe o primeiro vencimento.',
            'primeiro_vencimento.date_format' => 'Informe uma data válida.',
        ];
    }

    /** Valor em centavos, por manipulação de texto (sem float): "1500.5" → 150050. */
    public function valorCentavos(): int
    {
        [$reais, $centavos] = array_pad(explode('.', (string) $this->validated('valor')), 2, '');

        return (int) $reais * 100 + (int) str_pad($centavos, 2, '0');
    }

    /**
     * Aceita formato brasileiro e com ponto: "1.500,50", "1500,50", "1500.50" → "1500.50";
     * "1.500" (só milhar) → "1500".
     */
    private static function normalizarDecimal(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = str_replace(['R$', '%', ' ', "\u{00A0}"], '', (string) $valor);

        if (str_contains($valor, ',')) {
            return str_replace(',', '.', str_replace('.', '', $valor));
        }

        return preg_match('/^\d{1,3}(\.\d{3})+$/', $valor) ? str_replace('.', '', $valor) : $valor;
    }
}
