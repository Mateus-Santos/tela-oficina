<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitarOrcamentoPecaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Solicitação pública, sem acesso a dados privados.
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = $this->input('whatsapp');
        if (is_string($whatsapp)) {
            $this->merge(['whatsapp' => preg_replace('/\D+/', '', $whatsapp)]);
        }

        $uf = $this->input('uf');
        if (is_string($uf)) {
            $this->merge(['uf' => mb_strtoupper(trim($uf))]);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:2', 'max:120'],
            'whatsapp' => ['required', 'string', 'regex:/^[0-9]{10,13}$/'],
            // Legado: texto livre continua aceito sem itens selecionados.
            'peca' => ['required_without:itens', 'nullable', 'string', 'min:3', 'max:180'],
            'itens' => ['sometimes', 'array', 'max:10'],
            'itens.*' => ['required', 'array:produto_id,quantidade'],
            'itens.*.produto_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('produtos', 'id')->where(
                    static fn (Builder $query) => $query
                        ->where('status', true)
                        ->where('quantidade', '>', 0)
                ),
            ],
            'itens.*.quantidade' => ['required', 'integer', 'between:1,99'],
            'veiculo' => ['nullable', 'string', 'max:160'],
            'ano' => ['nullable', 'string', 'max:20'],
            'motor' => ['nullable', 'string', 'max:80'],
            'detalhes' => ['nullable', 'string', 'max:600'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'uf' => ['nullable', Rule::in([
                'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS',
                'MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC',
                'SP','SE','TO',
            ])],
            'utm_source' => ['nullable', 'string', 'max:150'],
            'utm_medium' => ['nullable', 'string', 'max:150'],
            'utm_campaign' => ['nullable', 'string', 'max:150'],
            'utm_content' => ['nullable', 'string', 'max:150'],
            'utm_term' => ['nullable', 'string', 'max:150'],
            'gclid' => ['nullable', 'string', 'max:200'],
            'aceite_privacidade' => ['required', 'accepted'],
            'website' => ['prohibited'], // Honeypot.
        ];
    }
}
