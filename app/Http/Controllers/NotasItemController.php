<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NotasItemController extends Controller
{
    public function index()
    {
        $notas = Nota::with(['cliente.pessoa', 'itens', 'veiculoscliente'])
        ->where('status', '!=', 'Cancelado')
        ->get();
        return view('notas_item.listar_notas_itens', compact('notas'));
    }

    public function create()
    {
        $ordemservicos = OrdemServico::all();
        $produtos = Produto::all();

        return view('notas_item.cadastro_notas_itens', compact('ordemservicos', 'produtos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'veiculo_cliente_id'        => 'nullable|integer',
            'itens'                     => 'required|array|min:1',
            'itens.*.itemable_type'     => ['required', 'string', \Illuminate\Validation\Rule::in(['App\Models\Produto', 'App\Models\OrdemServico'])],
            'itens.*.itemable_id'       => 'required|integer',
            'itens.*.descricao'         => 'required|string|max:250',
            'itens.*.quantidade'        => 'required|integer|min:1',
            'itens.*.valor_unitario'    => 'required|numeric|min:0',
            'itens.*.desconto'          => 'nullable|numeric|min:0',
            'itens.*.garantia_dias'     => 'nullable|integer|min:0',
            'km'                        => 'nullable|integer|min:0',
        ]);

        $itensEnviados = $request->input('itens');

        try {
            $nota = new Nota();

            $nota->cliente_id = $request->input('cliente_id') ?: null;
            $nota->veiculo_cliente_id = $request->input('veiculo_cliente_id') ?: null;
            $nota->tipo       = 'Venda';
            $nota->status     = 'Aberto';
            $nota->km = $request->input('km') ?: null;
            $subtotalGeral = 0;
            $descontoGeral = 0;

            foreach ($itensEnviados as $item) {
                $qtd = (int) $item['quantidade'];
                $vUnit = (float) $item['valor_unitario'];
                $desc = (float) ($item['desconto'] ?? 0);

                $subtotalGeral += ($qtd * $vUnit);
                $descontoGeral += $desc;
            }

            $nota->subtotal = $subtotalGeral;
            $nota->desconto = $descontoGeral;
            $nota->total    = $subtotalGeral - $descontoGeral;

            $nota->save();

            foreach ($itensEnviados as $dadosItem) {
                if ($dadosItem['itemable_type'] === 'App\Models\Produto') {
                    if (!Produto::where('id', $dadosItem['itemable_id'])->exists()) {
                        return redirect()->back()->withErrors(['itens' => 'Um dos produtos selecionados não existe.'])->withInput();
                    }
                } else if ($dadosItem['itemable_type'] === 'App\Models\OrdemServico') {
                    if (!OrdemServico::where('id', $dadosItem['itemable_id'])->exists()) {
                        return redirect()->back()->withErrors(['itens' => 'Uma das Ordens de Serviço é inválida.'])->withInput();
                    }
                }

                $item = new NotasItem();

                $item->nota_id = $nota->id;

                $item->itemable_type = $dadosItem['itemable_type'];
                $item->itemable_id   = $dadosItem['itemable_id'];

                $item->descricao = $dadosItem['descricao'];
                $item->quantidade = (int) $dadosItem['quantidade'];

                $valorUnitario = (float) $dadosItem['valor_unitario'];
                $desconto = (float) ($dadosItem['desconto'] ?? 0);

                $item->valor_unitario = $valorUnitario;
                $item->desconto = $desconto;
                $item->valor_total = ($valorUnitario * $item->quantidade) - $desconto;

                if (!empty($dadosItem['garantia_dias'])) {
                    $item->garantia_dias = (int) $dadosItem['garantia_dias'];
                    $item->garantia_inicio = now()->format('Y-m-d');
                    $item->garantia_fim = now()->addDays($item->garantia_dias)->format('Y-m-d');
                }

                $item->save();
            }

            return redirect()->route('notasitem.index')
                            ->with('success', 'Nota Fiscal criada com ' . count($itensEnviados) . ' itens!');

        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['erro_banco' => 'Falha ao salvar a venda: ' . $e->getMessage()])
                            ->withInput();
        }
    }

    public function edit(string $id)
    {
        // Carrega a nota com cliente, veículo e os itens com seus polimórficos
        $nota = Nota::with([
            'cliente.pessoa',
            'veiculoscliente',
            'notasitem.itemable'
        ])->findOrFail($id);

        $ordemservicos = OrdemServico::all();
        $produtos = Produto::all();

        return view('notas_item.editar_notas_itens', compact('nota', 'ordemservicos', 'produtos'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'cliente_id'          => 'nullable|integer',
            'veiculo_cliente_id'  => 'nullable|integer',
            'itens'               => 'required|array|min:1',
            'itens.*.id'          => 'nullable|integer', // ID do item existente
            'itens.*.itemable_type' => ['required', 'string', Rule::in(['App\Models\Produto', 'App\Models\OrdemServico'])],
            'itens.*.itemable_id'   => 'required|integer',
            'itens.*.descricao'     => 'required|string|max:250',
            'itens.*.quantidade'    => 'required|integer|min:1',
            'itens.*.valor_unitario'=> 'required|numeric|min:0',
            'itens.*.desconto'      => 'nullable|numeric|min:0',
            'itens.*.garantia_dias' => 'nullable|integer|min:0',
        ]);

        $nota = Nota::findOrFail($id);
        $itensEnviados = $request->input('itens', []);

        DB::beginTransaction();
        try {
            // 1. Atualiza dados principais da Nota
            $nota->cliente_id = $request->input('cliente_id') ?: null;
            $nota->veiculo_cliente_id = $request->input('veiculo_cliente_id') ?: null;
            $nota->km = $request->input('km') ?: null;

            $subtotalGeral = 0;
            $descontoGeral = 0;

            foreach ($itensEnviados as $itemData) {
                $qtd = (int) $itemData['quantidade'];
                $vUnit = (float) $itemData['valor_unitario'];
                $desc = (float) ($itemData['desconto'] ?? 0);

                $subtotalGeral += ($qtd * $vUnit);
                $descontoGeral += $desc;
            }

            $nota->subtotal = $subtotalGeral;
            $nota->desconto = $descontoGeral;
            $nota->total = $subtotalGeral - $descontoGeral;
            $nota->save();

            // 2. Coleta IDs enviados para manter (sincronização)
            $idsEnviados = array_filter(array_column($itensEnviados, 'id'));

            // Exclui do banco os itens da nota que NÃO foram enviados na requisição
            $nota->notasitem()->whereNotIn('id', $idsEnviados)->delete();

            // 3. Atualiza ou Cria os itens
            foreach ($itensEnviados as $dadosItem) {
                $itemId = $dadosItem['id'] ?? null;

                $qtd = (int) $dadosItem['quantidade'];
                $vUnit = (float) $dadosItem['valor_unitario'];
                $desc = (float) ($dadosItem['desconto'] ?? 0);
                $vTotal = ($qtd * $vUnit) - $desc;

                $dataToSave = [
                    'nota_id'       => $nota->id,
                    'itemable_type' => $dadosItem['itemable_type'],
                    'itemable_id'   => $dadosItem['itemable_id'],
                    'descricao'     => $dadosItem['descricao'],
                    'quantidade'    => $qtd,
                    'valor_unitario'=> $vUnit,
                    'desconto'      => $desc,
                    'valor_total'   => $vTotal,
                    'garantia_dias' => !empty($dadosItem['garantia_dias']) ? (int) $dadosItem['garantia_dias'] : null,
                ];

                if (!empty($dadosItem['garantia_dias'])) {
                    $dataToSave['garantia_inicio'] = now()->format('Y-m-d');
                    $dataToSave['garantia_fim'] = now()->addDays((int) $dadosItem['garantia_dias'])->format('Y-m-d');
                }

                if ($itemId) {
                    NotasItem::where('id', $itemId)->where('nota_id', $nota->id)->update($dataToSave);
                } else {
                    NotasItem::create($dataToSave);
                }
            }

            DB::commit();

            return redirect()->route('notasitem.index')
                ->with('success', 'Nota Fiscal #' . $nota->id . ' atualizada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['erro_banco' => 'Falha ao atualizar a Nota: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(string $id)
    {
        $item = NotasItem::findOrFail($id);
        $notaId = $item->nota_id;
        $item->delete();
        return redirect()->route('notas.show', $notaId)
            ->with('success', 'Item removido da nota com sucesso!');
    }
}
