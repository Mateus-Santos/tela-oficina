<?php

namespace App\Http\Controllers;

use App\Actions\Etapas\RealocarVinculosEtapa;
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
    public function index(
        Request $request
    ): View {
        $this->garantirAcesso(
            $request
        );

        $etapas =
            Etapa::query()
                ->withCount(['notas', 'ordensServico'])
                ->orderBy('ordem')
                ->orderBy('id')
                ->get();

        return view(
            'etapas.index',
            compact('etapas')
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $this->garantirAcesso(
            $request
        );

        $dados =
            $request->validate([
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
            $request->boolean(
                'aplica_nota'
            );

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
        $this->garantirAcesso(
            $request
        );

        $dados =
            $request->validate([
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

        $resultado = DB::transaction(
            function () use (
                $request,
                $etapa,
                $dados
            ) {
                // Serializar com movimentacoes de notas/OS em andamento.
                $etapa = Etapa::query()->lockForUpdate()->findOrFail($etapa->id);
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

                    return ['notas' => 0, 'os' => 0];
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
                    && $ativo
                ) {
                    throw ValidationException::withMessages([
                        'aplica_nota' => 'Uma etapa ativa precisa pertencer a Notas, O.S. ou ambos os fluxos.',
                    ]);
                }

                $movidos = app(RealocarVinculosEtapa::class)->execute(
                    $etapa,
                    ! $ativo || ! $aplicaNota,
                    ! $ativo || ! $aplicaOrdemServico,
                    $request->user()?->id
                );

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

                return $movidos;
            }, 3
        );

        return redirect()
            ->route('etapas.index')
            ->with(
                'success',
                sprintf('Etapa atualizada. %d nota(s) e %d O.S. realocada(s) para a etapa inicial.', $resultado['notas'], $resultado['os'])
            );
    }

    public function destroy(Request $request, Etapa $etapa): RedirectResponse
    {
        $this->garantirAcesso($request);

        DB::transaction(function () use ($etapa) {
            $etapa = Etapa::query()->lockForUpdate()->findOrFail($etapa->id);

            if ($etapa->tipo !== 'normal' || $etapa->ativo) {
                throw ValidationException::withMessages([
                    'etapa' => 'Somente etapas normais e inativas podem ser excluidas.',
                ]);
            }

            if ($etapa->notas()->exists() || $etapa->ordensServico()->exists()) {
                throw ValidationException::withMessages([
                    'etapa' => 'Exclusao bloqueada: etapa ainda possui Notas ou O.S. vinculadas.',
                ]);
            }

            // O historico guarda nomes, e suas FKs de etapa usam nullOnDelete.
            $etapa->delete();
        }, 3);

        return redirect()->route('etapas.index')
            ->with('success', 'Etapa inativa excluida. Historicos preservados.');
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
        abort_unless(
            $request
                ->user()
                ?->isAdmin(),
            403
        );
    }
}
