<?php

namespace App\Http\Controllers\PortalCliente;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Services\Marketing\LiberacaoPortalCliente;

final class PainelClienteMarketingController extends Controller
{
    public function __invoke(Request $request, LiberacaoPortalCliente $acesso): View
    {
        abort_unless($acesso->permitido($request->user(), 'portal_inicio'), 404);

        return view('portal-cliente.painel.index');
    }
}
