<?php

namespace App\Http\Controllers\PortalCliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortalCliente\ListarOrdensServicoClienteRequest;
use App\Services\PortalCliente\ConsultarNotasCliente;
use App\Services\PortalCliente\ConsultarOrdensServicoCliente;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrdensServicoClienteController extends Controller
{
    public function index(
        ListarOrdensServicoClienteRequest $request,
        ConsultarOrdensServicoCliente $consulta,
        ConsultarNotasCliente $notas
    ): View {
        $dados = $request->validated();

        $veiculoId = isset($dados['veiculo'])
            ? (int) $dados['veiculo']
            : null;

        $status = $dados['status'] ?? null;

        $ordens = $consulta
            ->consulta($request->user(), $veiculoId, $status)
            ->orderByDesc('data_abertura')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('portal-cliente.ordens-servico.index', [
            'ordens' => $ordens,
            'veiculos' => $notas->veiculosDoCliente($request->user()),
            'veiculoSelecionado' => $veiculoId,
            'statusSelecionado' => $status,
        ]);
    }

    public function show(
        Request $request,
        ConsultarOrdensServicoCliente $consulta,
        int $ordemServico
    ): View {
        // Consulta sempre limitada ao cliente autenticado.
        $registro = $consulta
            ->consulta($request->user())
            ->whereKey($ordemServico)
            ->firstOrFail();

        return view('portal-cliente.ordens-servico.show', [
            'ordem' => $registro,
        ]);
    }
}
