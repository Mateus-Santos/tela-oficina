<?php

namespace App\Http\Controllers\PortalCliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortalCliente\SalvarEnderecoClienteRequest;
use App\Models\Endereco;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnderecosClienteController extends Controller
{
    public function index(Request $request): View
    {
        $enderecos = Endereco::query()
            ->select([
                'id',
                'cep',
                'cidade',
                'bairro',
                'estado',
                'rua',
                'numero',
                'ponto_referencia',
            ])
            ->where('pessoa_id', $request->user()->pessoa_id)
            ->orderBy('id')
            ->paginate(10);

        return view('portal-cliente.enderecos.index', [
            'enderecos' => $enderecos,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->pessoa_id, 403);

        return view('portal-cliente.enderecos.form', [
            'endereco' => null,
        ]);
    }

    public function store(
        SalvarEnderecoClienteRequest $request
    ): RedirectResponse {
        // O proprietário vem da sessão, jamais do formulário.
        $request->user()
            ->pessoa
            ->enderecos()
            ->create($request->validated());

        return redirect()
            ->route('cliente.enderecos.index')
            ->with('success', 'Endereço cadastrado com sucesso.');
    }

    public function edit(
        Request $request,
        int $endereco
    ): View {
        $registro = $this->proprioEndereco($request, $endereco);

        return view('portal-cliente.enderecos.form', [
            'endereco' => $registro,
        ]);
    }

    public function update(
        SalvarEnderecoClienteRequest $request,
        int $endereco
    ): RedirectResponse {
        $registro = $this->proprioEndereco($request, $endereco);

        // Somente os campos validados são atualizados.
        $registro->update($request->validated());

        return redirect()
            ->route('cliente.enderecos.index')
            ->with('success', 'Endereço atualizado com sucesso.');
    }

    private function proprioEndereco(
        Request $request,
        int $endereco
    ): Endereco {
        abort_unless($request->user()->pessoa_id, 403);

        return Endereco::query()
            ->where('pessoa_id', $request->user()->pessoa_id)
            ->findOrFail($endereco);
    }
}
