<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NotasItemController extends Controller
{
    /**
     * Lista as notas.
     */
    public function index()
    {
        $notas = Nota::with([
            'cliente.pessoa',
            'itens',
            'veiculoscliente'
        ])
            ->where('status', '!=', 'Cancelado')
            ->get();

        return view(
            'notas_item.listar_notas_itens',
            compact('notas')
        );
    }

    /**
     * Exibe o formulário de cadastro.
     */
    public function create()
    {
        $ordemservicos = OrdemServico::all();
        $produtos = Produto::all();

        return view(
            'notas_item.cadastro_notas_itens',
            compact('ordemservicos', 'produtos')
        );
    }

    /**
     * Salva uma nova Nota e seus itens.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'nullable|integer',
            'veiculo_cliente_id' => 'nullable|integer',
            'km' => 'nullable|integer|min:0',
            'km_proxima_troca_oleo' => 'nullable|integer|min:0',

            'itens' => 'required|array|min:1',

            'itens.*.itemable_type' => [
                'required',
                'string',
                Rule::in([
                    Produto::class,
                    OrdemServico::class,
                ]),
            ],

            'itens.*.itemable_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'itens.*.descricao' => [
                'required',
                'string',
                'max:250',
            ],

            'itens.*.quantidade' => [
                'required',
                'integer',
                'min:1',
            ],

            'itens.*.valor_unitario' => [
                'required',
                'numeric',
                'min:0',
            ],

            'itens.*.desconto' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'itens.*.garantia_dias' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $itensEnviados = $request->input('itens', []);

        DB::beginTransaction();

        try {
            /*
             * =========================================================
             * 1. VALIDAR CLIENTE E VEÍCULO
             * =========================================================
             */

            if ($request->filled('cliente_id')) {
                $clienteExiste = DB::table('clientes')
                    ->where('id', $request->cliente_id)
                    ->exists();

                if (!$clienteExiste) {
                    throw new \Exception(
                        'O cliente informado não existe.'
                    );
                }
            }

            if ($request->filled('veiculo_cliente_id')) {
                $veiculoExiste = DB::table('veiculos_clientes')
                    ->where('id', $request->veiculo_cliente_id)
                    ->exists();

                if (!$veiculoExiste) {
                    throw new \Exception(
                        'O veículo informado não existe.'
                    );
                }
            }

            /*
             * =========================================================
             * 2. VALIDAR TODOS OS ITENS ANTES DE CRIAR A NOTA
             * =========================================================
             *
             * Isso evita criar uma Nota parcialmente caso algum
             * produto ou serviço seja inválido.
             */

            $subtotalGeral = 0;
            $descontoGeral = 0;

            foreach ($itensEnviados as $index => $dadosItem) {
                $tipo = $dadosItem['itemable_type'];
                $itemId = (int) $dadosItem['itemable_id'];

                $quantidade = (int) $dadosItem['quantidade'];
                $valorUnitario = (float) $dadosItem['valor_unitario'];
                $desconto = (float) ($dadosItem['desconto'] ?? 0);

                /*
                 * -----------------------------------------------------
                 * Verifica se o Produto/O.S. realmente existe
                 * -----------------------------------------------------
                 */

                if ($tipo === Produto::class) {
                    $itemExiste = Produto::where('id', $itemId)->exists();

                    if (!$itemExiste) {
                        throw new \Exception(
                            "O produto informado no item " .
                            ($index + 1) .
                            " não existe."
                        );
                    }
                }

                if ($tipo === OrdemServico::class) {
                    $itemExiste = OrdemServico::where('id', $itemId)->exists();

                    if (!$itemExiste) {
                        throw new \Exception(
                            "A Ordem de Serviço informada no item " .
                            ($index + 1) .
                            " não existe."
                        );
                    }
                }

                /*
                 * -----------------------------------------------------
                 * Validação financeira
                 * -----------------------------------------------------
                 */

                $subtotalItem = $quantidade * $valorUnitario;

                if ($desconto > $subtotalItem) {
                    throw new \Exception(
                        "O desconto do item " .
                        ($index + 1) .
                        " não pode ser maior que o valor do item."
                    );
                }

                $subtotalGeral += $subtotalItem;
                $descontoGeral += $desconto;
            }

            /*
             * =========================================================
             * 3. CALCULAR TOTAL DA NOTA
             * =========================================================
             */

            $totalGeral = max(
                0,
                $subtotalGeral - $descontoGeral
            );

            /*
             * =========================================================
             * 4. CRIAR A NOTA
             * =========================================================
             */

            $nota = new Nota();

            $nota->cliente_id = $request->input('cliente_id') ?: null;

            $nota->veiculo_cliente_id =
                $request->input('veiculo_cliente_id') ?: null;

            $nota->tipo = 'Venda';

            $nota->status = 'Aberto';

            // KM do veículo na chegada
            $nota->km = $request->input('km') ?: null;

            // KM previsto para próxima troca
            $nota->km_proxima_troca_oleo =
                $request->input('km_proxima_troca_oleo') ?: null;

            $nota->subtotal = $subtotalGeral;

            $nota->desconto = $descontoGeral;

            $nota->total = $totalGeral;

            $nota->save();

            /*
             * =========================================================
             * 5. CRIAR OS ITENS DA NOTA
             * =========================================================
             */

            foreach ($itensEnviados as $dadosItem) {
                $quantidade =
                    (int) $dadosItem['quantidade'];

                $valorUnitario =
                    (float) $dadosItem['valor_unitario'];

                $desconto =
                    (float) ($dadosItem['desconto'] ?? 0);

                $valorTotal =
                    max(
                        0,
                        ($quantidade * $valorUnitario) - $desconto
                    );

                $item = new NotasItem();

                $item->nota_id = $nota->id;

                $item->itemable_type =
                    $dadosItem['itemable_type'];

                $item->itemable_id =
                    $dadosItem['itemable_id'];

                $item->descricao =
                    $dadosItem['descricao'];

                $item->quantidade =
                    $quantidade;

                $item->valor_unitario =
                    $valorUnitario;

                $item->desconto =
                    $desconto;

                $item->valor_total =
                    $valorTotal;

                /*
                 * -----------------------------------------------------
                 * Garantia
                 * -----------------------------------------------------
                 */

                if (
                    isset($dadosItem['garantia_dias']) &&
                    $dadosItem['garantia_dias'] !== '' &&
                    (int) $dadosItem['garantia_dias'] > 0
                ) {
                    $garantiaDias =
                        (int) $dadosItem['garantia_dias'];

                    $item->garantia_dias =
                        $garantiaDias;

                    $item->garantia_inicio =
                        now()->format('Y-m-d');

                    $item->garantia_fim =
                        now()
                            ->addDays($garantiaDias)
                            ->format('Y-m-d');
                } else {
                    $item->garantia_dias = null;
                    $item->garantia_inicio = null;
                    $item->garantia_fim = null;
                }

                $item->save();
            }

            /*
             * =========================================================
             * 6. CONFIRMAR TRANSACTION
             * =========================================================
             */

            DB::commit();

            return redirect()
                ->route('notasitem.index')
                ->with(
                    'success',
                    'Nota Fiscal criada com ' .
                    count($itensEnviados) .
                    ' itens!'
                );

        } catch (\Exception $e) {

            /*
             * Se qualquer coisa falhar, desfaz absolutamente tudo.
             */

            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors([
                    'erro_banco' =>
                        'Falha ao salvar a venda: ' .
                        $e->getMessage()
                ])
                ->withInput();
        }
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(string $id)
    {
        $nota = Nota::with([
            'cliente.pessoa',
            'veiculosCliente',
            'itens.itemable'
        ])->findOrFail($id);

        $ordemservicos = OrdemServico::all();
        $produtos = Produto::all();

        return view(
            'notas_item.editar_notas_itens',
            compact(
                'nota',
                'ordemservicos',
                'produtos'
            )
        );
    }

    /**
     * Atualiza uma Nota existente.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'cliente_id' => 'nullable|integer',
            'veiculo_cliente_id' => 'nullable|integer',
            'km' => 'nullable|integer|min:0',
            'km_proxima_troca_oleo' => 'nullable|integer|min:0',

            'itens' => 'required|array|min:1',

            'itens.*.id' => 'nullable|integer',

            'itens.*.itemable_type' => [
                'required',
                'string',
                Rule::in([
                    Produto::class,
                    OrdemServico::class,
                ]),
            ],

            'itens.*.itemable_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'itens.*.descricao' => [
                'required',
                'string',
                'max:250',
            ],

            'itens.*.quantidade' => [
                'required',
                'integer',
                'min:1',
            ],

            'itens.*.valor_unitario' => [
                'required',
                'numeric',
                'min:0',
            ],

            'itens.*.desconto' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'itens.*.garantia_dias' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $nota = Nota::findOrFail($id);

        $itensEnviados =
            $request->input('itens', []);

        DB::beginTransaction();

        try {
            /*
             * =========================================================
             * 1. VALIDAR CLIENTE E VEÍCULO
             * =========================================================
             */

            if ($request->filled('cliente_id')) {
                $clienteExiste = DB::table('clientes')
                    ->where('id', $request->cliente_id)
                    ->exists();

                if (!$clienteExiste) {
                    throw new \Exception(
                        'O cliente informado não existe.'
                    );
                }
            }

            if ($request->filled('veiculo_cliente_id')) {
                $veiculoExiste = DB::table('veiculos_clientes')
                    ->where('id', $request->veiculo_cliente_id)
                    ->exists();

                if (!$veiculoExiste) {
                    throw new \Exception(
                        'O veículo informado não existe.'
                    );
                }
            }

            /*
             * =========================================================
             * 2. VALIDAR TODOS OS ITENS
             * =========================================================
             */

            $subtotalGeral = 0;
            $descontoGeral = 0;

            foreach ($itensEnviados as $index => $dadosItem) {
                $tipo =
                    $dadosItem['itemable_type'];

                $itemId =
                    (int) $dadosItem['itemable_id'];

                $quantidade =
                    (int) $dadosItem['quantidade'];

                $valorUnitario =
                    (float) $dadosItem['valor_unitario'];

                $desconto =
                    (float) ($dadosItem['desconto'] ?? 0);

                /*
                 * Verifica Produto
                 */

                if ($tipo === Produto::class) {
                    if (
                        !Produto::where(
                            'id',
                            $itemId
                        )->exists()
                    ) {
                        throw new \Exception(
                            "O produto informado no item " .
                            ($index + 1) .
                            " não existe."
                        );
                    }
                }

                /*
                 * Verifica O.S.
                 */

                if ($tipo === OrdemServico::class) {
                    if (
                        !OrdemServico::where(
                            'id',
                            $itemId
                        )->exists()
                    ) {
                        throw new \Exception(
                            "A Ordem de Serviço informada no item " .
                            ($index + 1) .
                            " não existe."
                        );
                    }
                }

                /*
                 * Validação do desconto
                 */

                $subtotalItem =
                    $quantidade * $valorUnitario;

                if ($desconto > $subtotalItem) {
                    throw new \Exception(
                        "O desconto do item " .
                        ($index + 1) .
                        " não pode ser maior que o valor do item."
                    );
                }

                $subtotalGeral +=
                    $subtotalItem;

                $descontoGeral +=
                    $desconto;
            }

            /*
             * =========================================================
             * 3. ATUALIZAR DADOS DA NOTA
             * =========================================================
             */

            $nota->cliente_id =
                $request->input('cliente_id') ?: null;

            $nota->veiculo_cliente_id =
                $request->input('veiculo_cliente_id') ?: null;

            $nota->km =
                $request->input('km') ?: null;

            $nota->km_proxima_troca_oleo =
                $request->input('km_proxima_troca_oleo') ?: null;

            $nota->subtotal =
                $subtotalGeral;

            $nota->desconto =
                $descontoGeral;

            $nota->total =
                max(
                    0,
                    $subtotalGeral - $descontoGeral
                );

            $nota->save();

            /*
             * =========================================================
             * 4. IDENTIFICAR ITENS EXISTENTES
             * =========================================================
             */

            $idsEnviados = collect($itensEnviados)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            /*
             * Remove somente os itens pertencentes a esta Nota
             * que não foram enviados novamente.
             */

            if (!empty($idsEnviados)) {
                $nota->itens()
                    ->whereNotIn(
                        'id',
                        $idsEnviados
                    )
                    ->delete();
            } else {
                $nota->itens()->delete();
            }

            /*
             * =========================================================
             * 5. ATUALIZAR / CRIAR ITENS
             * =========================================================
             */

            foreach ($itensEnviados as $dadosItem) {
                $itemId =
                    $dadosItem['id'] ?? null;

                $quantidade =
                    (int) $dadosItem['quantidade'];

                $valorUnitario =
                    (float) $dadosItem['valor_unitario'];

                $desconto =
                    (float) ($dadosItem['desconto'] ?? 0);

                $valorTotal =
                    max(
                        0,
                        ($quantidade * $valorUnitario) - $desconto
                    );

                $dataToSave = [
                    'nota_id' =>
                        $nota->id,

                    'itemable_type' =>
                        $dadosItem['itemable_type'],

                    'itemable_id' =>
                        $dadosItem['itemable_id'],

                    'descricao' =>
                        $dadosItem['descricao'],

                    'quantidade' =>
                        $quantidade,

                    'valor_unitario' =>
                        $valorUnitario,

                    'desconto' =>
                        $desconto,

                    'valor_total' =>
                        $valorTotal,

                    'garantia_dias' =>
                        null,

                    'garantia_inicio' =>
                        null,

                    'garantia_fim' =>
                        null,
                ];

                /*
                 * -----------------------------------------------------
                 * Garantia
                 * -----------------------------------------------------
                 */

                if (
                    isset($dadosItem['garantia_dias']) &&
                    $dadosItem['garantia_dias'] !== '' &&
                    (int) $dadosItem['garantia_dias'] > 0
                ) {
                    $garantiaDias =
                        (int) $dadosItem['garantia_dias'];

                    $dataToSave['garantia_dias'] =
                        $garantiaDias;

                    $dataToSave['garantia_inicio'] =
                        now()->format('Y-m-d');

                    $dataToSave['garantia_fim'] =
                        now()
                            ->addDays($garantiaDias)
                            ->format('Y-m-d');
                }

                /*
                 * -----------------------------------------------------
                 * Atualiza item existente
                 * -----------------------------------------------------
                 */

                if ($itemId) {
                    $itemAtualizado = NotasItem::where(
                        'id',
                        $itemId
                    )
                        ->where(
                            'nota_id',
                            $nota->id
                        )
                        ->update($dataToSave);

                    if ($itemAtualizado === 0) {
                        throw new \Exception(
                            'Um dos itens enviados para atualização não pertence a esta Nota.'
                        );
                    }
                }

                /*
                 * -----------------------------------------------------
                 * Cria item novo
                 * -----------------------------------------------------
                 */

                else {
                    NotasItem::create(
                        $dataToSave
                    );
                }
            }

            /*
             * =========================================================
             * 6. CONFIRMAR TRANSACTION
             * =========================================================
             */

            DB::commit();

            return redirect()
                ->route('notasitem.index')
                ->with(
                    'success',
                    'Nota Fiscal #' .
                    $nota->id .
                    ' atualizada com sucesso!'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors([
                    'erro_banco' =>
                        'Falha ao atualizar a Nota: ' .
                        $e->getMessage()
                ])
                ->withInput();
        }
    }

    /**
     * Remove um item da Nota.
     */
    public function destroy(string $id)
    {
        $item = NotasItem::findOrFail($id);

        $notaId = $item->nota_id;

        $item->delete();

        return redirect()
            ->route('notas.show', $notaId)
            ->with(
                'success',
                'Item removido da nota com sucesso!'
            );
    }
}
