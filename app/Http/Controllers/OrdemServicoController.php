<?php

namespace App\Http\Controllers;

use App\Actions\OrdemServico\CancelarOrdemServico;
use App\Actions\OrdemServico\CriarOrdemServico;
use App\Actions\OrdemServico\FinalizarOrdemServico;
use App\Models\Etapa;
use App\Models\OrdemServico;
use App\Models\NotasItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\SetorServico;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrdemServicoController extends Controller
{
    public function index(Request $request)
    {
        $query = OrdemServico::query()
            ->with([
                'cliente.pessoa',
                'veiculosCliente.clientes.pessoa',
                'veiculosCliente.veiculo.montadora',
                'setorServico',
                'etapa',
            ]);

        if ($request->filled('id')) {
            $query->where(
                'id',
                $request->input('id')
            );
        }

        if ($request->filled('status')) {
            if ($request->input('status') !== 'todos') {
                $query->where(
                    'status',
                    $request->input('status')
                );
            }
        } elseif (!$request->filled('id')) {
            $query->where('status', 'aberta');
        }

        if ($request->filled('etapa_id')) {
            $query->where(
                'etapa_id',
                $request->integer('etapa_id')
            );
        }

        if ($request->filled('cliente')) {
            $cliente = trim(
                (string) $request->input('cliente')
            );

            $query->whereHas(
                'cliente.pessoa',
                function ($pessoaQuery) use ($cliente) {
                    $pessoaQuery->where(
                        'nome',
                        'like',
                        "%{$cliente}%"
                    );
                }
            );
        }

        if ($request->filled('placa')) {
            $placa = strtoupper(
                preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    $request->input('placa')
                )
            );

            $query->whereHas(
                'veiculosCliente',
                function ($veiculoClienteQuery) use ($placa) {
                    $veiculoClienteQuery->where(
                        'placa',
                        'like',
                        "%{$placa}%"
                    );
                }
            );
        }

        if ($request->filled('setor')) {
            $query->where(
                'setor_servico_id',
                $request->input('setor')
            );
        }

        if ($request->filled('descricao')) {
            $descricao = trim(
                (string) $request->input('descricao')
            );

            $query->where(
                'descricao',
                'like',
                "%{$descricao}%"
            );
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate(
                'data_abertura',
                '>=',
                $request->input('data_inicio')
            );
        }

        if ($request->filled('data_fim')) {
            $query->whereDate(
                'data_abertura',
                '<=',
                $request->input('data_fim')
            );
        }

        $ordemservicos = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $setorservicos = SetorServico::query()
            ->orderBy('setor')
            ->get();

        $etapas = Etapa::query()->paraOrdemServico()->ativas()->get();

        return view(
            'ordemservico.listar_os',
            compact(
                'ordemservicos',
                'setorservicos',
                'etapas'
            )
        );
    }

    public function create()
    {
        $setorservicos = SetorServico::query()
            ->orderBy('setor')
            ->get();

        return view(
            'ordemservico.cadastro_os',
            compact('setorservicos')
        );
    }

    public function store(
        Request $request,
        CriarOrdemServico $criarOrdemServico
    ) {
        $dados = $request->validate(
            [
                'cliente_id' => [
                    'required',
                    'integer',
                    'exists:clientes,id',
                ],

                'veiculo_cliente_id' => [
                    'required',
                    'integer',
                    'exists:veiculos_clientes,id',
                ],

                'setor_servico_id' => [
                    'required',
                    'integer',
                    'exists:setor_servicos,id',
                ],

                'descricao' => [
                    'nullable',
                    'string',
                ],

                'valor' => [
                    'required',
                ],
            ],
            [
                'cliente_id.required' => 'Selecione o cliente responsável pela OS.',

                'cliente_id.integer' => 'O cliente selecionado é inválido.',

                'cliente_id.exists' => 'O cliente selecionado não existe.',

                'veiculo_cliente_id.required' => 'Selecione o veículo.',

                'veiculo_cliente_id.integer' => 'O veículo selecionado é inválido.',

                'veiculo_cliente_id.exists' => 'O veículo selecionado não existe.',

                'setor_servico_id.required' => 'Selecione o setor de serviço.',

                'setor_servico_id.integer' => 'O setor selecionado é inválido.',

                'setor_servico_id.exists' => 'O setor selecionado não existe.',

                'valor.required' => 'Informe o valor da ordem de serviço.',
            ]
        );

        $resultado = app(\App\Services\Nota\CriarOsComNota::class)
            ->execute($dados, $request->user()->id);

        return redirect()
            ->route('notas.show', $resultado['nota']->id)
            ->with('success', 'O.S. criada e vinculada a Nota #' . $resultado['nota']->id . '.');
    }

    // U03: somente OS aberta e sem vinculo com Nota pode ser editada.
    public function edit(string $id)
    {
        $ordemServico = OrdemServico::with([
            'cliente.pessoa', 'veiculosCliente.veiculo', 'setorServico',
        ])->findOrFail($id);
        if ($ordemServico->status !== 'aberta' || $this->possuiNota($ordemServico)) {
            return redirect()->route('ordemservicos.index')->with(
                'error', 'Esta OS esta encerrada ou vinculada a Nota e nao pode ser editada.'
            );
        }
        $setorservicos = SetorServico::orderBy('setor')->get();
        return view('ordemservico.editar_os', compact('ordemServico', 'setorservicos'));
    }

    public function update(Request $request, string $id)
    {
        $dados = $request->validate([
            'setor_servico_id' => ['required', 'integer', 'exists:setor_servicos,id'],
            'descricao' => ['required', 'string', 'max:250'],
            'valor' => ['required', 'string', 'regex:/^\d{1,9}(?:[,.]\d{1,2})?$/'],
        ]);
        DB::transaction(function () use ($id, $dados) {
            $os = OrdemServico::query()->lockForUpdate()->findOrFail($id);
            if ($os->status !== 'aberta' || $this->possuiNota($os)) {
                throw ValidationException::withMessages([
                    'os' => 'OS encerrada ou vinculada a Nota: edicao bloqueada.',
                ]);
            }
            // Cliente, veiculo, etapa, status e historico nao sao editaveis aqui.
            $os->update([
                'setor_servico_id' => (int) $dados['setor_servico_id'],
                'descricao' => $dados['descricao'],
                'valor' => str_replace(',', '.', $dados['valor']),
            ]);
        });
        return redirect()->route('ordemservicos.index')->with(
            'success', 'Ordem de servico atualizada com seguranca.'
        );
    }

    private function possuiNota(OrdemServico $os): bool
    {
        return NotasItem::query()
            ->where('itemable_type', OrdemServico::class)
            ->where('itemable_id', $os->id)->exists();
    }

    public function anexos(OrdemServico $ordemservico)
    {
        $ordemservico->load('anexosVinculos.anexo');
        return view('ordemservico.anexos', compact('ordemservico'));
    }

    public function finalizar(
        Request $request,
        OrdemServico $ordemservico,
        FinalizarOrdemServico $finalizarOrdemServico
    ) {
        try {
            $finalizarOrdemServico->execute(
                $ordemservico,
                $request->user()->id
            );
        } catch (InvalidArgumentException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        return redirect()
            ->route('ordemservicos.index')
            ->with(
                'success',
                "Ordem de Serviço #{$ordemservico->id} finalizada com sucesso!"
            );
    }

    public function cancelar(
        OrdemServico $ordemservico,
        CancelarOrdemServico $cancelarOrdemServico
    ) {
        try {
            $cancelarOrdemServico->execute(
                $ordemservico
            );
        } catch (InvalidArgumentException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        return redirect()
            ->route('ordemservicos.index')
            ->with(
                'success',
                "Ordem de Serviço #{$ordemservico->id} cancelada com sucesso!"
            );
    }

    public function destroy(string $id)
    {
        $ordemservico = OrdemServico::findOrFail(
            $id
        );

        if ($ordemservico->status !== 'aberta') {
            return redirect()
                ->route('ordemservicos.index')
                ->with(
                    'error',
                    'Somente Ordens de Serviço abertas podem ser excluídas.'
                );
        }

        // FEUX: nao destruir uma O.S. utilizada como item de Nota.
        if (\App\Models\NotasItem::query()
            ->where('itemable_type', \App\Models\OrdemServico::class)
            ->where('itemable_id', $ordemservico->id)->exists()) {
            return redirect()->route('ordemservicos.index')
                ->with('error', 'O.S. vinculada a Nota nao pode ser excluida.');
        }

        $ordemservico->delete();

        return redirect()
            ->route('ordemservicos.index')
            ->with(
                'success',
                'Ordem de serviço excluída com sucesso!'
            );
    }
}
