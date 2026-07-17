<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdemServicoItem;
use App\Models\OrdemServico;
use App\Models\Produto;

class OrdemServicoItemController extends Controller
{
    public function index()
    {
        // Traz os itens com os relacionamentos carregados
        $os_itens = OrdemServicoItem::with(['ordemServico', 'itemable'])->get();
        return view('os_item.listar_os_item', compact('os_itens'));
    }

    public function create()
    {
        $ordemservicos = OrdemServico::all();
        $produtos = Produto::all();

        return view('os_item.cadastro_os_item', compact('ordemservicos', 'produtos'));
    }

    public function store(Request $request)
    {
        // 1. Validação do Header da OS e da estrutura do Array dinâmico enviado
        $request->validate([
            'ordem_servico_id'          => 'required|exists:ordem_servicos,id',
            
            // Garante que o usuário adicionou pelo menos 1 item (Produto ou Serviço) na tabela
            'itens'                     => 'required|array|min:1',
            
            // Valida individualmente as propriedades de cada linha do array injetado
            'itens.*.itemable_type'     => 'required|string',
            'itens.*.itemable_id'       => 'required|integer',
            'itens.*.descricao'         => 'required|string|max:250',
            'itens.*.quantidade'        => 'required|integer|min:1',
            'itens.*.valor_unitario'    => 'required|numeric|min:0',
            'itens.*.desconto'          => 'nullable|numeric|min:0',
            'itens.*.garantia_dias'     => 'nullable|integer|min:0',
        ]);

        $ordemServicoId = $request->input('ordem_servico_id');
        $itensEnviados = $request->input('itens');

        try {
            foreach ($itensEnviados as $dadosItem) {
                $item = new OrdemServicoItem();
                $item->ordem_servico_id = $ordemServicoId;
                
                // Mapeamento Polimórfico
                $item->itemable_type = $dadosItem['itemable_type'];
                $item->itemable_id   = $dadosItem['itemable_id'];
                
                $item->descricao = $dadosItem['descricao'];
                $item->quantidade = (int) $dadosItem['quantidade'];
                
                $valorUnitario = (float) $dadosItem['valor_unitario'];
                $desconto = (float) ($dadosItem['desconto'] ?? 0);
                
                $item->valor_unitario = $valorUnitario;
                $item->desconto = $desconto;
                
                // Cálculo matemático do subtotal do item
                $item->valor_total = ($valorUnitario * $item->quantidade) - $desconto;

                // Regra de data automática baseada nos dias de garantia
                if (!empty($dadosItem['garantia_dias'])) {
                    $item->garantia_dias = (int) $dadosItem['garantia_dias'];
                    $item->garantia_inicio = now()->format('Y-m-d');
                    $item->garantia_fim = now()->addDays($item->garantia_dias)->format('Y-m-d');
                }

                $item->save();
            }

            return redirect()->route('ordemservicoitem.index')
                             ->with('success', count($itensEnviados) . ' Itens salvos na O.S com sucesso!');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->withErrors(['erro_banco' => 'Falha crítica ao tentar salvar os itens: ' . $e->getMessage()])
                             ->withInput();
        }
    }

    public function edit(string $id)
    {
        $item = OrdemServicoItem::findOrFail($id);
        $ordemservicos = OrdemServico::all();
        $produtos = Produto::all();
        
        return view('os_item.editar_os_item', compact('item', 'ordemservicos', 'produtos', 'servicos'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'ordem_servico_id' => 'required|exists:ordem_servicos,id',
            'descricao'        => 'required|string|max:250',
            'quantidade'       => 'required|integer|min:1',
            'valor_unitario'   => 'required|numeric',
            'desconto'         => 'nullable|numeric',
        ]);

        $item = OrdemServicoItem::findOrFail($id);
        $item->ordem_servico_id = $request->input('ordem_servico_id');
        $item->descricao = $request->input('descricao');
        $item->quantidade = $request->input('quantidade');
        $item->valor_unitario = $request->input('valor_unitario');
        $item->desconto = $request->input('desconto', 0);
        $item->valor_total = ($item->valor_unitario * $item->quantidade) - $item->desconto;
        
        // Atualiza datas se fornecidas
        $item->garantia_dias = $request->input('garantia_dias');
        $item->garantia_inicio = $request->input('garantia_inicio');
        $item->garantia_fim = $request->input('garantia_fim');

        $item->update();

        return redirect()->route('ordem-servico-itens.index')->with('success', 'Item atualizado!');
    }

    public function destroy(string $id)
    {
        OrdemServicoItem::findOrFail($id)->delete();
        return redirect()->route('ordem-servico-itens.index')->with('success', 'Item removido!');
    }
}