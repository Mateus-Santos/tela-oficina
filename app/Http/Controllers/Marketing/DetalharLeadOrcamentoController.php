<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingLead;
use Illuminate\Contracts\View\View;

class DetalharLeadOrcamentoController extends Controller
{
    public function __invoke(MarketingLead $lead): View
    {
        return view('marketing.leads.show', [
            'lead' => $lead->load(['itens.produto', 'cliente.pessoa', 'nota']),
        ]);
    }
}
