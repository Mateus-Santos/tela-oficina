<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Forge\ListarPacotesForge;
use App\Services\Forge\RelatoriosValidacaoForge;
use Illuminate\Contracts\View\View;

final class PainelForgeController extends Controller
{
    public function __invoke(
        ListarPacotesForge $listagem,
        RelatoriosValidacaoForge $relatorios
    ): View {
        $raiz = (string) config('forge.staging_root');
        $consulta = $listagem->listar($raiz);

        foreach ($consulta['pacotes'] as &$pacote) {
            $pacote['relatorio'] = $relatorios->ultimo($raiz, $pacote['nome']);
        }
        unset($pacote);

        return view('admin.forge.index', compact('consulta'));
    }
}
