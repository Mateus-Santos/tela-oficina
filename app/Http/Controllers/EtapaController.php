<?php

namespace App\Http\Controllers;

use App\Models\Etapa;
use App\Models\Nota;
use App\Models\OrdemServico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EtapaController extends Controller
{
    public function index(Request $request): View
    {
        $this->garantirAcesso($request);

        $etapas = Etapa::query()
            ->orderBy('ordem')
            ->orderBy('id')
            ->get();

        return view(
            'etapas.index',
            compact('etapas')
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $this->garantirAcesso($request);

        $dados = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:100',
            ],

            'descricao' => [
                'nullable',
                'string',
                'max:255',
            ],

            'cor' => [
                'required',
                'in:primary,secondary,success,danger,warning,info,dark',
            ],

            'ordem' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],
        ]);

        $aplicaNota =
            $request->boolean('aplica_nota');

        $aplicaOrdemServico =
            $request->boolean(
                'aplica_ordem_servico'
            );

        if (
            ! $aplicaNota
            && ! $aplicaOrdemServico
        ) {
            throw ValidationException::withMessages([
                'aplica_nota' => 'A etapa deve ser utilizada em Nota, Ordem de Serviço ou em ambos.',
            ]);
        }

        Etapa::create([
            'nome' => $dados['nome'],

            'slug' => $this->gerarSlugUnico(
                $dados['nome']
            ),

            'descricao' => $dados['descricao']
                ?? null,

            'cor' => $dados['cor'],

            'ordem' => $dados['ordem'],

            'tipo' => 'normal',

            'aplica_nota' => $aplicaNota,

            'aplica_ordem_servico' => $aplicaOrdemServico,

            'ativo' => true,
        ]);

        return redirect()
            ->route('etapas.index')
            ->with(
                'success',
                'Etapa criada com sucesso.'
            );
    }

    public function update(
        Request $request,
        Etapa $etapa
    ): RedirectResponse {
        $this->garantirAcesso($request);

        $dados = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:100',
            ],

            'descricao' => [
                'nullable',
                'string',
                'max:255',
            ],

            'cor' => [
                'required',
                'in:primary,secondary,success,danger,warning,info,dark',
            ],

            'ordem' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],
        ]);

        DB::transaction(
            function () use (
                $request,
                $etapa,
                $dados
            ) {
                /*
                 * Inicial/final são estruturais.
                 *
                 * Podem ser renomeadas e reordenadas,
                 * mas permanecem ativas e aplicáveis
                 * aos fluxos que já atendem.
                 */
                if (
                    in_array(
                        $etapa->tipo,
                        [
                            'inicial',
                            'final',
                        ],
                        true
                    )
                ) {
                    $etapa->update([
                        'nome' => $dados['nome'],

                        'descricao' => $dados['descricao']
                            ?? null,

                        'cor' => $dados['cor'],

                        'ordem' => $dados['ordem'],

                        'ativo' => true,
                    ]);

                    return;
                }

                $aplicaNota =
                    $request->boolean(
                        'aplica_nota'
                    );

                $aplicaOrdemServico =
                    $request->boolean(
                        'aplica_ordem_servico'
                    );

                $ativo =
                    $request->boolean(
                        'ativo'
                    );

                if (
                    ! $aplicaNota
                    && ! $aplicaOrdemServico
                ) {
                    throw ValidationException::withMessages([
                        'aplica_nota' => 'A etapa deve ser utilizada em Nota, Ordem de Serviço ou em ambos.',
                    ]);
                }

                /*
                 * Não podemos remover a etapa do fluxo
                 * enquanto houver registros ativos nela.
                 */
                if (
                    ! $aplicaNota
                    && $etapa->aplica_nota
                ) {
                    $possuiNotasAbertas =
                        Nota::query()
                            ->where(
                                'etapa_id',
                                $etapa->id
                            )
                            ->where(
                                'status',
                                'Aberto'
                            )
                            ->exists();

                    if ($possuiNotasAbertas) {
                        throw ValidationException::withMessages([
                            'aplica_nota' => 'Esta etapa possui Notas abertas e não pode ser removida do fluxo de Notas.',
                        ]);
                    }
                }

                if (
                    ! $aplicaOrdemServico
                    && $etapa
                        ->aplica_ordem_servico
                ) {
                    $possuiOrdensAbertas =
                        OrdemServico::query()
                            ->where(
                                'etapa_id',
                                $etapa->id
                            )
                            ->where(
                                'status',
                                'aberta'
                            )
                            ->exists();

                    if ($possuiOrdensAbertas) {
                        throw ValidationException::withMessages([
                            'aplica_ordem_servico' => 'Esta etapa possui Ordens de Serviço abertas e não pode ser removida do fluxo de O.S.',
                        ]);
                    }
                }

                $etapa->update([
                    'nome' => $dados['nome'],

                    'descricao' => $dados['descricao']
                        ?? null,

                    'cor' => $dados['cor'],

                    'ordem' => $dados['ordem'],

                    'aplica_nota' => $aplicaNota,

                    'aplica_ordem_servico' => $aplicaOrdemServico,

                    'ativo' => $ativo,
                ]);
            }
        );

        return redirect()
            ->route('etapas.index')
            ->with(
                'success',
                'Etapa atualizada com sucesso.'
            );
    }

    private function gerarSlugUnico(
        string $nome
    ): string {
        $base =
            Str::slug($nome);

        $slug =
            $base;

        $contador =
            2;

        while (
            Etapa::query()
                ->where(
                    'slug',
                    $slug
                )
                ->exists()
        ) {
            $slug =
                $base
                .'-'
                .$contador;

            $contador++;
        }

        return $slug;
    }

    private function garantirAcesso(
        Request $request
    ): void {
        abort_if(
            ! $request->user()
            || $request->user()->permitions == 2,
            403
        );
    }
}
