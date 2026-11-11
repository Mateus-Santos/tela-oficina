<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\AtualizarStatusLeadRequest;
use App\Models\MarketingLead;
use Illuminate\Http\RedirectResponse;

class AtualizarStatusLeadController extends Controller
{
    public function __invoke(AtualizarStatusLeadRequest $request, MarketingLead $lead): RedirectResponse
    {
        // Somente o campo status é persistido; nenhuma outra propriedade do lead é alterada.
        $lead->update([
            'status' => $request->validated('status'),
        ]);

        return redirect()
            ->route('marketing.leads.index')
            ->with('marketing_status_sucesso', 'Situação do orçamento atualizada.');
    }
}
