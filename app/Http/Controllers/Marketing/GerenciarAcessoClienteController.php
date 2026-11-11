<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\MarketingClienteAcesso;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class GerenciarAcessoClienteController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Schema::hasTable('marketing_cliente_acessos'), 404);

        $dados = $request->validate([
            'busca' => ['nullable', 'string', 'max:80'],
        ]);
        $busca = trim($dados['busca'] ?? '');

        $clientes = Cliente::query()
            ->with(['pessoa:id,nome', 'marketingAcesso'])
            ->when($busca !== '', static function ($consulta) use ($busca): void {
                $consulta->where(function ($filtrada) use ($busca): void {
                    $filtrada->whereHas('pessoa', static function ($pessoas) use ($busca): void {
                        $pessoas->where('nome', 'like', '%'.addcslashes($busca, '%_\\').'%');
                    });
                    if (ctype_digit($busca)) {
                        $filtrada->orWhere('clientes.id', (int) $busca);
                    }
                });
            })
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('marketing.clientes-acessos.index', [
            'clientes' => $clientes,
            'busca' => $busca,
        ]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        abort_unless(Schema::hasTable('marketing_cliente_acessos'), 404);

        $dados = $request->validate([
            'portal_inicio' => ['required', 'boolean'],
            'solicitacoes' => ['required', 'boolean'],
        ]);

        DB::transaction(static function () use ($cliente, $dados, $request): void {
            MarketingClienteAcesso::query()->updateOrCreate(
                ['cliente_id' => $cliente->id],
                [
                    'portal_inicio' => (bool) $dados['portal_inicio'],
                    'solicitacoes' => (bool) $dados['solicitacoes'],
                    'atualizado_por' => $request->user()->id,
                ]
            );
        });

        return redirect()->route('marketing.clientes-acessos.index')
            ->with('marketing_acessos_sucesso', 'Permissões do portal atualizadas.');
    }
}
