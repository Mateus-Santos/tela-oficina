<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\VeiculosCliente;
use Exception;
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
            'veiculosCliente',
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
        return view(
            'notas_item.cadastro_notas_itens'
        );
    }

    /**
     * Salva uma nova Nota e seus itens.
     */
    public function store(Request $request)
    {
        /*
         * =========================================================
         * 0. NORMALIZAR DADOS RECEBIDOS DO FORMULÁRIO
         * =========================================================
         */
        $this->normalizarItens($request);

        $request->validate([
            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id',
            ],

            'veiculo_cliente_id' => [
                'nullable',
                'integer',
                'exists:veiculos_clientes,id',
            ],

            'km' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'km_proxima_troca_oleo' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'itens' => [
                'required',
                'array',
                'min:1',
            ],

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

        $itensEnviados = $request->input(
            'itens',
            []
        );

        DB::beginTransaction();

        try {
            /*
             * =========================================================
             * 1. VALIDAR CLIENTE E VEÍCULO
             * =========================================================
             */
            $this->validarClienteVeiculo(
                $request->filled('cliente_id')
                    ? (int) $request->input('cliente_id')
                    : null,

                $request->filled('veiculo_cliente_id')
                    ? (int) $request->input('veiculo_cliente_id')
                    : null
            );

            /*
             * =========================================================
             * 2. VALIDAR TODOS OS ITENS ANTES DE CRIAR A NOTA
             * =========================================================
             */
            $subtotalGeral = 0;
            $descontoGeral = 0;

            foreach ($itensEnviados as $index => $dadosItem) {
                $tipo = $dadosItem['itemable_type'];

                $itemId = (int) $dadosItem['itemable_id'];

                $quantidade = (int) $dadosItem['quantidade'];

                $valorUnitario = (float) $dadosItem['valor_unitario'];

                $desconto = (float) (
                    $dadosItem['desconto']
                    ?? 0
                );

                /*
                 * Verifica Produto.
                 */
                if ($tipo === Produto::class) {
                    $itemExiste = Produto::query()
                        ->where(
                            'id',
                            $itemId
                        )
                        ->exists();

                    if (!$itemExiste) {
                        throw new Exception(
                            'O produto informado no item '
                            . ($index + 1)
                            . ' não existe.'
                        );
                    }
                }

                /*
                 * Verifica O.S.
                 */
                if ($tipo === OrdemServico::class) {
                    $this->validarOrdemServicoDaNota(
                        $itemId,
                        $index,
                        $request
                    );
                }

                /*
                 * Validação financeira.
                 */
                $subtotalItem =
                    $quantidade
                    * $valorUnitario;

                if ($desconto > $subtotalItem) {
                    throw new Exception(
                        'O desconto do item '
                        . ($index + 1)
                        . ' não pode ser maior que o valor do item.'
                    );
                }

                $subtotalGeral +=
                    $subtotalItem;

                $descontoGeral +=
                    $desconto;
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

            $nota->cliente_id =
                $request->input('cliente_id')
                ?: null;

            $nota->veiculo_cliente_id =
                $request->input('veiculo_cliente_id')
                ?: null;

            $nota->tipo = 'Venda';

            /*
             * Toda nova nota começa aberta.
             *
             * O estoque somente será movimentado
             * na finalização.
             */
            $nota->status = 'Aberto';

            /*
             * KM do veículo na chegada.
             */
            $nota->km =
                $request->input('km')
                ?: null;

            /*
             * KM previsto para próxima troca.
             */
            $nota->km_proxima_troca_oleo =
                $request->input(
                    'km_proxima_troca_oleo'
                )
                ?: null;

            $nota->subtotal =
                $subtotalGeral;

            /*
             * Soma dos descontos dos itens.
             */
            $nota->desconto =
                $descontoGeral;

            $nota->total =
                $totalGeral;

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
                    (float) (
                        $dadosItem['desconto']
                        ?? 0
                    );

                $valorTotal = max(
                    0,
                    (
                        $quantidade
                        * $valorUnitario
                    ) - $desconto
                );

                $item = new NotasItem();

                $item->nota_id =
                    $nota->id;

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
                 * Garantia.
                 */
                if (
                    isset(
                        $dadosItem['garantia_dias']
                    )
                    && $dadosItem['garantia_dias'] !== ''
                    && (int) $dadosItem['garantia_dias'] > 0
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
                    'Nota Fiscal criada com '
                    . count($itensEnviados)
                    . ' itens!'
                );
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors([
                    'erro_banco' =>
                        'Falha ao salvar a venda: '
                        . $e->getMessage(),
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
            'veiculosCliente.veiculo.montadora',
            'itens.itemable',
        ])->findOrFail($id);

        /*
         * Notas finalizadas ou canceladas
         * não podem ser editadas.
         */
        if ($nota->status !== 'Aberto') {
            return redirect()
                ->route(
                    'notas.show',
                    $nota->id
                )
                ->withErrors([
                    'nota' =>
                        'Notas finalizadas ou canceladas não podem ser editadas.',
                ]);
        }

        return view(
            'notas_item.editar_notas_itens',
            compact('nota')
        );
    }

    /**
     * Atualiza uma Nota existente.
     */
    public function update(
        Request $request,
        string $id
    ) {
        $nota = Nota::findOrFail($id);

        /*
         * =========================================================
         * PROTEÇÃO DE STATUS
         * =========================================================
         */
        if ($nota->status !== 'Aberto') {
            return redirect()
                ->route(
                    'notas.show',
                    $nota->id
                )
                ->withErrors([
                    'nota' =>
                        'Somente notas com status Aberto podem ser editadas.',
                ]);
        }

        /*
         * Normaliza os dados antes da validação.
         */
        $this->normalizarItens($request);

        $request->validate([
            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id',
            ],

            'veiculo_cliente_id' => [
                'nullable',
                'integer',
                'exists:veiculos_clientes,id',
            ],

            'km' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'km_proxima_troca_oleo' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'itens' => [
                'required',
                'array',
                'min:1',
            ],

            'itens.*.id' => [
                'nullable',
                'integer',
            ],

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

        $itensEnviados =
            $request->input(
                'itens',
                []
            );

        DB::beginTransaction();

        try {
            /*
             * =========================================================
             * 1. REVALIDAR A NOTA DENTRO DA TRANSACTION
             * =========================================================
             */
            $nota = Nota::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($nota->status !== 'Aberto') {
                throw new Exception(
                    'A nota não está mais aberta e não pode ser alterada.'
                );
            }

            /*
             * =========================================================
             * 2. VALIDAR CLIENTE E VEÍCULO
             * =========================================================
             */
            $this->validarClienteVeiculo(
                $request->filled('cliente_id')
                    ? (int) $request->input('cliente_id')
                    : null,

                $request->filled('veiculo_cliente_id')
                    ? (int) $request->input('veiculo_cliente_id')
                    : null
            );

            /*
             * =========================================================
             * 3. VALIDAR TODOS OS ITENS
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
                    (float) (
                        $dadosItem['desconto']
                        ?? 0
                    );

                /*
                 * Verifica Produto.
                 */
                if ($tipo === Produto::class) {
                    $itemExiste =
                        Produto::query()
                            ->where(
                                'id',
                                $itemId
                            )
                            ->exists();

                    if (!$itemExiste) {
                        throw new Exception(
                            'O produto informado no item '
                            . ($index + 1)
                            . ' não existe.'
                        );
                    }
                }

                /*
                 * Verifica O.S.
                 */
                if ($tipo === OrdemServico::class) {
                    $this->validarOrdemServicoDaNota(
                        $itemId,
                        $index,
                        $request
                    );
                }

                /*
                 * Validação do desconto.
                 */
                $subtotalItem =
                    $quantidade
                    * $valorUnitario;

                if ($desconto > $subtotalItem) {
                    throw new Exception(
                        'O desconto do item '
                        . ($index + 1)
                        . ' não pode ser maior que o valor do item.'
                    );
                }

                $subtotalGeral +=
                    $subtotalItem;

                $descontoGeral +=
                    $desconto;
            }

            /*
             * =========================================================
             * 4. ATUALIZAR DADOS DA NOTA
             * =========================================================
             */
            $nota->cliente_id =
                $request->input('cliente_id')
                ?: null;

            $nota->veiculo_cliente_id =
                $request->input(
                    'veiculo_cliente_id'
                )
                ?: null;

            $nota->km =
                $request->input('km')
                ?: null;

            $nota->km_proxima_troca_oleo =
                $request->input(
                    'km_proxima_troca_oleo'
                )
                ?: null;

            $nota->subtotal =
                $subtotalGeral;

            $nota->desconto =
                $descontoGeral;

            $nota->total =
                max(
                    0,
                    $subtotalGeral
                    - $descontoGeral
                );

            $nota->save();

            /*
             * =========================================================
             * 5. IDENTIFICAR ITENS EXISTENTES
             * =========================================================
             */
            $idsEnviados =
                collect($itensEnviados)
                    ->pluck('id')
                    ->filter()
                    ->map(
                        fn ($id) =>
                            (int) $id
                    )
                    ->values()
                    ->all();

            /*
             * Remove somente itens desta Nota
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
                $nota->itens()
                    ->delete();
            }

            /*
             * =========================================================
             * 6. ATUALIZAR / CRIAR ITENS
             * =========================================================
             */
            foreach ($itensEnviados as $dadosItem) {
                $itemId =
                    $dadosItem['id']
                    ?? null;

                $quantidade =
                    (int) $dadosItem['quantidade'];

                $valorUnitario =
                    (float) $dadosItem['valor_unitario'];

                $desconto =
                    (float) (
                        $dadosItem['desconto']
                        ?? 0
                    );

                $valorTotal =
                    max(
                        0,
                        (
                            $quantidade
                            * $valorUnitario
                        ) - $desconto
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
                 * Garantia.
                 */
                if (
                    isset(
                        $dadosItem['garantia_dias']
                    )
                    && $dadosItem['garantia_dias'] !== ''
                    && (int) $dadosItem['garantia_dias'] > 0
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
                 * Atualiza item existente.
                 */
                if ($itemId) {
                    $itemAtualizado =
                        NotasItem::query()
                            ->where(
                                'id',
                                $itemId
                            )
                            ->where(
                                'nota_id',
                                $nota->id
                            )
                            ->update(
                                $dataToSave
                            );

                    if ($itemAtualizado === 0) {
                        throw new Exception(
                            'Um dos itens enviados para atualização não pertence a esta Nota.'
                        );
                    }
                } else {
                    /*
                     * Cria item novo.
                     */
                    NotasItem::create(
                        $dataToSave
                    );
                }
            }

            /*
             * =========================================================
             * 7. CONFIRMAR TRANSACTION
             * =========================================================
             */
            DB::commit();

            return redirect()
                ->route('notasitem.index')
                ->with(
                    'success',
                    'Nota Fiscal #'
                    . $nota->id
                    . ' atualizada com sucesso!'
                );
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors([
                    'erro_banco' =>
                        'Falha ao atualizar a Nota: '
                        . $e->getMessage(),
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

        $nota = Nota::findOrFail(
            $item->nota_id
        );

        /*
         * Itens somente podem ser removidos
         * enquanto a Nota estiver aberta.
         */
        if ($nota->status !== 'Aberto') {
            return redirect()
                ->route(
                    'notas.show',
                    $nota->id
                )
                ->withErrors([
                    'nota' =>
                        'Não é possível remover itens de uma nota finalizada ou cancelada.',
                ]);
        }

        $item->delete();

        /*
         * Recalcula os valores da Nota.
         */
        $nota->load('itens');

        $subtotalGeral =
            $nota->itens->sum(
                function ($item) {
                    return
                        (float) $item->quantidade
                        * (float) $item->valor_unitario;
                }
            );

        $descontoGeral =
            $nota->itens->sum(
                function ($item) {
                    return
                        (float) $item->desconto;
                }
            );

        $nota->update([
            'subtotal' =>
                $subtotalGeral,

            'desconto' =>
                $descontoGeral,

            'total' =>
                max(
                    0,
                    $subtotalGeral
                    - $descontoGeral
                ),
        ]);

        return redirect()
            ->route(
                'notas.show',
                $nota->id
            )
            ->with(
                'success',
                'Item removido da nota com sucesso!'
            );
    }

    /**
     * Valida o vínculo entre cliente e veículo da Nota.
     *
     * Regras:
     *
     * - Sem veículo: cliente pode ser null.
     * - Com veículo: precisa existir um cliente responsável.
     * - O cliente precisa estar vinculado ao veículo.
     */
    private function validarClienteVeiculo(
        ?int $clienteId,
        ?int $veiculoClienteId
    ): void {
        /*
         * Venda de balcão ou Nota somente com cliente.
         */
        if (!$veiculoClienteId) {
            return;
        }

        /*
         * Veículo selecionado exige cliente responsável.
         */
        if (!$clienteId) {
            throw new Exception(
                'Selecione o cliente responsável pelo veículo.'
            );
        }

        $vinculoExiste =
            VeiculosCliente::query()
                ->where(
                    'id',
                    $veiculoClienteId
                )
                ->whereHas(
                    'clientes',
                    function ($query) use ($clienteId) {
                        $query->where(
                            'clientes.id',
                            $clienteId
                        );
                    }
                )
                ->exists();

        if (!$vinculoExiste) {
            throw new Exception(
                'O cliente informado não está vinculado ao veículo selecionado.'
            );
        }
    }

    /**
     * Valida se uma O.S. adicionada à Nota
     * pertence ao mesmo cliente e veículo selecionados.
     */
    private function validarOrdemServicoDaNota(
        int $ordemServicoId,
        int $index,
        Request $request
    ): void {
        $ordemServico =
            OrdemServico::query()
                ->select([
                    'id',
                    'cliente_id',
                    'veiculo_cliente_id',
                ])
                ->find($ordemServicoId);

        if (!$ordemServico) {
            throw new Exception(
                'A Ordem de Serviço informada no item '
                . ($index + 1)
                . ' não existe.'
            );
        }

        /*
         * Se a Nota possui cliente,
         * a O.S. precisa pertencer ao mesmo cliente.
         */
        if (
            $request->filled('cliente_id')
            && $ordemServico->cliente_id
            && (int) $ordemServico->cliente_id
                !== (int) $request->input('cliente_id')
        ) {
            throw new Exception(
                'A Ordem de Serviço do item '
                . ($index + 1)
                . ' pertence a outro cliente.'
            );
        }

        /*
         * Se a Nota possui veículo,
         * a O.S. precisa pertencer ao mesmo veículo.
         */
        if (
            $request->filled('veiculo_cliente_id')
            && $ordemServico->veiculo_cliente_id
            && (int) $ordemServico->veiculo_cliente_id
                !== (int) $request->input('veiculo_cliente_id')
        ) {
            throw new Exception(
                'A Ordem de Serviço do item '
                . ($index + 1)
                . ' pertence a outro veículo.'
            );
        }
    }

    /**
     * Normaliza os dados dos itens antes da validação.
     *
     * Responsabilidades:
     *
     * 1. Converter "produto" para Produto::class.
     * 2. Converter "os" para OrdemServico::class.
     * 3. Corrigir barras duplicadas no namespace.
     * 4. Converter números brasileiros.
     * 5. Normalizar quantidade.
     */
    private function normalizarItens(
        Request $request
    ): void {
        $itens =
            $request->input('itens');

        if (!is_array($itens)) {
            return;
        }

        foreach ($itens as $index => &$item) {
            if (!is_array($item)) {
                continue;
            }

            /*
             * =========================================================
             * ITEMABLE TYPE
             * =========================================================
             */
            if (
                isset(
                    $item['itemable_type']
                )
            ) {
                $tipo = trim(
                    (string) $item['itemable_type']
                );

                /*
                 * Corrige barras duplicadas.
                 */
                $tipo = str_replace(
                    '\\\\',
                    '\\',
                    $tipo
                );

                /*
                 * Aceita aliases usados pelo JavaScript.
                 */
                if (
                    $tipo === 'produto'
                    || $tipo === 'Produto'
                    || $tipo === Produto::class
                ) {
                    $item['itemable_type'] =
                        Produto::class;
                } elseif (
                    $tipo === 'os'
                    || $tipo === 'OS'
                    || $tipo === 'OrdemServico'
                    || $tipo === OrdemServico::class
                ) {
                    $item['itemable_type'] =
                        OrdemServico::class;
                }
            }

            /*
             * =========================================================
             * VALOR UNITÁRIO
             * =========================================================
             */
            if (
                array_key_exists(
                    'valor_unitario',
                    $item
                )
            ) {
                $item['valor_unitario'] =
                    $this->normalizarNumero(
                        $item['valor_unitario']
                    );
            }

            /*
             * =========================================================
             * DESCONTO
             * =========================================================
             */
            if (
                array_key_exists(
                    'desconto',
                    $item
                )
            ) {
                $valorDesconto =
                    $this->normalizarNumero(
                        $item['desconto']
                    );

                $item['desconto'] =
                    $valorDesconto === ''
                        ? null
                        : $valorDesconto;
            }

            /*
             * =========================================================
             * QUANTIDADE
             * =========================================================
             */
            if (
                isset(
                    $item['quantidade']
                )
                && is_numeric(
                    $item['quantidade']
                )
            ) {
                $quantidade =
                    (float) str_replace(
                        ',',
                        '.',
                        (string) $item['quantidade']
                    );

                if (
                    $quantidade >= 1
                    && floor($quantidade) === $quantidade
                ) {
                    $item['quantidade'] =
                        (int) $quantidade;
                }
            }

            /*
             * =========================================================
             * GARANTIA
             * =========================================================
             */
            if (
                isset(
                    $item['garantia_dias']
                )
                && $item['garantia_dias'] !== ''
                && is_numeric(
                    $item['garantia_dias']
                )
            ) {
                $item['garantia_dias'] =
                    (int) $item['garantia_dias'];
            }
        }

        unset($item);

        $request->merge([
            'itens' => $itens,
        ]);
    }

    /**
     * Converte número brasileiro para formato aceito pelo PHP.
     *
     * Exemplos:
     *
     * 222,22   => 222.22
     * 1.234,56 => 1234.56
     * 1000.50  => 1000.50
     * 0,00     => 0.00
     */
    private function normalizarNumero(
        $valor
    ): string {
        if ($valor === null) {
            return '';
        }

        $valor = trim(
            (string) $valor
        );

        if ($valor === '') {
            return '';
        }

        /*
         * Remove espaços.
         */
        $valor = str_replace(
            ' ',
            '',
            $valor
        );

        /*
         * Formato:
         *
         * 1.234,56
         */
        if (
            str_contains(
                $valor,
                '.'
            )
            && str_contains(
                $valor,
                ','
            )
        ) {
            $valor = str_replace(
                '.',
                '',
                $valor
            );

            $valor = str_replace(
                ',',
                '.',
                $valor
            );

            return $valor;
        }

        /*
         * Formato:
         *
         * 222,22
         */
        if (
            str_contains(
                $valor,
                ','
            )
        ) {
            return str_replace(
                ',',
                '.',
                $valor
            );
        }

        /*
         * Já está no padrão:
         *
         * 222.22
         */
        return $valor;
    }
}
