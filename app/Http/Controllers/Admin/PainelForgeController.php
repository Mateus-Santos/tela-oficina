<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Forge\ListarPacotesForge;
use Illuminate\Contracts\View\View;

final class PainelForgeController extends Controller
{
    public function __invoke(ListarPacotesForge $listagem): View
    {
        return view('admin.forge.index', [
            'consulta' => $listagem->listar(
                (string) config('forge.staging_root')
            ),
        ]);
    }
}
