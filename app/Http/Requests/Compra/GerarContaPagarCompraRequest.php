<?php

namespace App\Http\Requests\Compra;

use Illuminate\Foundation\Http\FormRequest;

class GerarContaPagarCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parcelas' => [
                'required',
                'array',
                'min:1',
            ],

            'parcelas.*.numero' => [
                'required',
                'integer',
                'min:1',
            ],

            'parcelas.*.valor' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'parcelas.*.data_vencimento' => [
                'required',
                'date_format:Y-m-d',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $parcelas = $this->input('parcelas', []);

            if (!is_array($parcelas) || empty($parcelas)) {
                return;
            }

            $datasAnteriores = null;

            foreach ($parcelas as $indice => $parcela) {
                $data = $parcela['data_vencimento'] ?? null;

                if (!$data) {
                    continue;
                }

                $dataAtual = \DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $data
                );

                if (!$dataAtual) {
                    continue;
                }

                if (
                    $datasAnteriores !== null
                    && $dataAtual < $datasAnteriores
                ) {
                    $validator->errors()->add(
                        "parcelas.{$indice}.data_vencimento",
                        'As datas de vencimento devem seguir ordem cronológica.'
                    );
                }

                $datasAnteriores = $dataAtual;
            }
        });
    }

    public function messages(): array
    {
        return [
            'parcelas.required' =>
                'Informe as parcelas da conta a pagar.',

            'parcelas.array' =>
                'As parcelas informadas são inválidas.',

            'parcelas.min' =>
                'Informe pelo menos uma parcela.',

            'parcelas.*.numero.required' =>
                'O número da parcela é obrigatório.',

            'parcelas.*.numero.integer' =>
                'O número da parcela deve ser inteiro.',

            'parcelas.*.numero.min' =>
                'O número da parcela deve ser maior que zero.',

            'parcelas.*.valor.required' =>
                'O valor da parcela é obrigatório.',

            'parcelas.*.valor.numeric' =>
                'O valor da parcela deve ser numérico.',

            'parcelas.*.valor.gt' =>
                'O valor da parcela deve ser maior que zero.',

            'parcelas.*.data_vencimento.required' =>
                'A data de vencimento da parcela é obrigatória.',

            'parcelas.*.data_vencimento.date_format' =>
                'A data de vencimento deve estar no formato YYYY-MM-DD.',
        ];
    }
}
