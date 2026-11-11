<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingLead;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MarketingLeadsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filtros = $request->validate([
            'status' => ['nullable', 'in:novo,em_atendimento,aguardando_cliente,concluido,descartado'],
            'busca' => ['nullable', 'string', 'max:80'],
        ]);

        $status = $filtros['status'] ?? '';
        $busca = trim($filtros['busca'] ?? '');

        $query = MarketingLead::query()->select([
            'id', 'protocolo', 'nome', 'whatsapp', 'peca', 'veiculo',
            'status', 'utm_source', 'utm_campaign', 'created_at',
        ]);

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($busca !== '') {
            $query->where(function ($subquery) use ($busca): void {
                $subquery->where('protocolo', 'like', '%'.$busca.'%')
                    ->orWhere('peca', 'like', '%'.$busca.'%')
                    ->orWhere('nome', 'like', '%'.$busca.'%');
            });
        }

        return view('marketing.leads.index', [
            'leads' => $query->orderByDesc('id')->paginate(15)->withQueryString(),
            'totalLeads' => MarketingLead::query()->count(),
            'leadsNovos' => MarketingLead::query()->where('status', 'novo')->count(),
            'statusSelecionado' => $status,
            'busca' => $busca,
            'opcoesStatus' => MarketingLead::STATUS_LABELS,
        ]);
    }
}
