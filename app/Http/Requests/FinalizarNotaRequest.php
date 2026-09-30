<?php

namespace App\Http\Requests;

use App\Models\Nota;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinalizarNotaRequest extends FormRequest
{
    private ?bool $notaJaPossuiContaReceber = null;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $financeiroObrigatorio =
            !$this->notaPossuiContaReceber();

        return [
            'categoria_financeira_id' => [
                $financeiroObrigatorio
                    ? 'required'
                    : 'nullable',

                'integer',

                Rule::exists(
                    'categorias_financeiras',
                    'id'
                )->where(
                    function ($query) {
                        $query
                            ->where(
                                'tipo',
                                'entrada'
                            )
                            ->where(
                                'ativo',
                                true
                            );
                    }
                ),
            ],

            'parcelas' => [
                $financeiroObrigatorio
                    ? 'required'
                    : 'nullable',

                'array',

                $financeiroObrigatorio
                    ? 'min:1'
                    : null,
            ],

            'parcelas.*.numero' => [
                $financeiroObrigatorio
                    ? 'required'
                    : 'nullable',

                'integer',
                'min:1',
                'distinct',
            ],

            'parcelas.*.valor' => [
                $financeiroObrigatorio
                    ? 'required'
                    : 'nullable',

                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'parcelas.*.data_vencimento' => [
                $financeiroObrigatorio
                    ? 'required'
                    : 'nullable',

                'date_format:Y-m-d',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_financeira_id.required' =>
                'Informe a categoria financeira.',

            'categoria_financeira_id.exists' =>
                'A categoria financeira informada não existe, está inativa ou não é uma categoria de entrada.',

            'parcelas.required' =>
                'Informe ao menos uma parcela.',

            'parcelas.array' =>
                'As parcelas informadas são inválidas.',

            'parcelas.min' =>
                'Informe ao menos uma parcela.',

            'parcelas.*.numero.required' =>
                'Informe o número da parcela.',

            'parcelas.*.numero.integer' =>
                'O número da parcela deve ser um número inteiro.',

            'parcelas.*.numero.min' =>
                'O número da parcela deve ser maior que zero.',

            'parcelas.*.numero.distinct' =>
                'Existem números de parcelas duplicados.',

            'parcelas.*.valor.required' =>
                'Informe o valor da parcela.',

            'parcelas.*.valor.numeric' =>
                'O valor da parcela deve ser numérico.',

            'parcelas.*.valor.gt' =>
                'O valor da parcela deve ser maior que zero.',

            'parcelas.*.valor.decimal' =>
                'O valor da parcela deve possuir no máximo duas casas decimais.',

            'parcelas.*.data_vencimento.required' =>
                'Informe a data de vencimento da parcela.',

            'parcelas.*.data_vencimento.date_format' =>
                'A data de vencimento da parcela é inválida.',
        ];
    }

    private function notaPossuiContaReceber(): bool
    {
        if (
            $this->notaJaPossuiContaReceber
            !== null
        ) {
            return $this->notaJaPossuiContaReceber;
        }

        /*
         * Sua rota de finalizar recebe:
         *
         * finalizar(Request $request, string $id, ...)
         *
         * Portanto buscamos primeiro o parâmetro "id".
         */
        $notaId =
            $this->route('id');

        if (!$notaId) {
            $nota =
                $this->route('nota');

            if ($nota instanceof Nota) {
                $notaId = $nota->id;
            } elseif ($nota) {
                $notaId = $nota;
            }
        }

        if (!$notaId) {
            /*
             * Se não conseguirmos descobrir a Nota pela rota,
             * mantemos o comportamento seguro:
             * financeiro obrigatório.
             */
            return $this->notaJaPossuiContaReceber =
                false;
        }

        return $this->notaJaPossuiContaReceber =
            Nota::query()
                ->whereKey($notaId)
                ->whereHas('contaReceber')
                ->exists();
    }
}
