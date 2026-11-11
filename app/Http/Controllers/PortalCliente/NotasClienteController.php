<?php

namespace App\Http\Controllers\PortalCliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortalCliente\ListarNotasClienteRequest;
use App\Services\PortalCliente\ConsultarNotasCliente;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NotasClienteController extends Controller
{
    public function index(
        ListarNotasClienteRequest $request,
        ConsultarNotasCliente $consulta
    ): View {
        $dados = $request->validated();

        $veiculoId = isset($dados['veiculo'])
            ? (int) $dados['veiculo']
            : null;

        $notas = $consulta
            ->consulta($request->user(), $veiculoId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('portal-cliente.notas.index', [
            'notas' => $notas,
            'veiculos' => $consulta->veiculosDoCliente($request->user()),
            'veiculoSelecionado' => $veiculoId,
        ]);
    }

    public function show(
        Request $request,
        ConsultarNotasCliente $consulta,
        int $nota
    ): View {
        // Nunca consultar diretamente por ID sem cliente_id.
        $registro = $consulta
            ->consulta($request->user())
            ->whereKey($nota)
            ->firstOrFail();

        return view('portal-cliente.notas.show', [
            'nota' => $registro,
        ]);
    }
}
