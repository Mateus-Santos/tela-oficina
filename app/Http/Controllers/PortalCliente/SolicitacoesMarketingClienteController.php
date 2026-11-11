<?php

namespace App\Http\Controllers\PortalCliente;

use App\Http\Controllers\Controller;
use App\Models\MarketingLead;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Services\Marketing\LiberacaoPortalCliente;

final class SolicitacoesMarketingClienteController extends Controller
{
    public function index(Request $request, LiberacaoPortalCliente $acesso): View
    {
        abort_unless($acesso->permitido($request->user(), 'solicitacoes'), 404);

        $clienteId = $request->user()->cliente?->id;

        $consulta = MarketingLead::query()->select([
            'id', 'cliente_id', 'protocolo', 'peca', 'status', 'created_at',
        ]);

        if ($clienteId === null) {
            // Conta sem cadastro de cliente nunca recebe solicitacoes.
            $consulta->whereRaw('1 = 0');
        } else {
            // Vinculo administrativo expresso; jamais correlacionar pelo WhatsApp.
            $consulta->where('cliente_id', $clienteId);
        }

        return view('portal-cliente.solicitacoes.index', [
            'solicitacoes' => $consulta->orderByDesc('id')
                ->paginate(12),
        ]);
    }

    public function show(Request $request, LiberacaoPortalCliente $acesso, int $solicitacao): View
    {
        abort_unless($acesso->permitido($request->user(), 'solicitacoes'), 404);

        $clienteId = $request->user()->cliente?->id;
        abort_if($clienteId === null, 404);

        $lead = MarketingLead::query()
            ->where('cliente_id', $clienteId)
            ->with(['itens', 'nota:id,cliente_id,status'])
            ->findOrFail($solicitacao);

        // Nao divulgar link para nota associada incorretamente a outro cliente.
        $notaVisivel = $lead->nota !== null
            && (int) $lead->nota->cliente_id === (int) $clienteId;

        return view('portal-cliente.solicitacoes.show', [
            'solicitacao' => $lead,
            'notaVisivel' => $notaVisivel,
        ]);
    }
}
