<?php

namespace App\Http\Controllers;

use App\Actions\SetorServico\AtualizarSetorServico;
use App\Actions\SetorServico\CriarSetorServico;
use App\Http\Requests\SetorServico\StoreSetorServicoRequest;
use App\Http\Requests\SetorServico\UpdateSetorServicoRequest;
use App\Models\SetorServico;
use Illuminate\Http\Request;

class SetorServicoController extends Controller
{
    public function index(Request $request)
    {
        $setores = SetorServico::query()
            ->when($request->filled('setor'), function ($query) use ($request) {
                $query->where('setor', 'like', '%' . $request->setor . '%');
            })
            ->when($request->filled('nivel'), function ($query) use ($request) {
                $query->where('nivel', $request->nivel);
            })
            ->orderBy('setor')
            ->paginate(10)
            ->withQueryString();

        $niveis = SetorServico::query()
            ->whereNotNull('nivel')
            ->where('nivel', '!=', '')
            ->distinct()
            ->orderBy('nivel')
            ->pluck('nivel');

        return view('setor-servico.listar_setores', compact('setores', 'niveis'));
    }

    public function create()
    {
        return view('.cadastrar_setor');
    }

    public function store(
        StoreSetorServicoRequest $request,
        CriarSetorServico $criarSetorServico
    ) {
        $criarSetorServico->execute($request->validated());

        return redirect()
            ->route('setor-servicos.index')
            ->with('success', 'Setor de serviço cadastrado com sucesso!');
    }

    public function show(SetorServico $setorServico)
    {
        $setorServico->loadCount('ordemServicos');

        return view('setor-servico.visualizar_setor', compact('setorServico'));
    }

    public function edit(SetorServico $setorServico)
    {
        return view('setor-servico.editar_setor', compact('setorServico'));
    }

    public function update(
        UpdateSetorServicoRequest $request,
        SetorServico $setorServico,
        AtualizarSetorServico $atualizarSetorServico
    ) {
        $atualizarSetorServico->execute(
            $setorServico,
            $request->validated()
        );

        return redirect()
            ->route('setor-servicos.show', $setorServico)
            ->with('success', 'Setor de serviço atualizado com sucesso!');
    }

    public function destroy(SetorServico $setorServico)
    {
        if ($setorServico->ordemServicos()->exists()) {
            return redirect()
                ->route('setor-servicos.index')
                ->with(
                    'error',
                    'Este setor não pode ser excluído porque possui ordens de serviço vinculadas.'
                );
        }

        $setorServico->delete();

        return redirect()
            ->route('setor-servicos.index')
            ->with('success', 'Setor de serviço excluído com sucesso!');
    }
}
