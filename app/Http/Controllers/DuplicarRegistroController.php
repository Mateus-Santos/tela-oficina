<?php

namespace App\Http\Controllers;

use App\Actions\Compra\CriarCompra;
use App\Actions\Notas\CriarNota;
use App\Actions\OrdemServico\CriarOrdemServico;
use App\Models\Compra;
use App\Models\Nota;
use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DuplicarRegistroController extends Controller
{
    private function validarAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    public function nota(Request $request, Nota $nota)
    {
        $this->validarAdmin($request);
        $nota->load('itens');
        return view('duplicacao.nota', compact('nota'));
    }

    public function duplicarNota(
        Request $request,
        Nota $nota,
        CriarNota $criarNota,
        CriarOrdemServico $criarOrdemServico
    ) {
        $this->validarAdmin($request);
        $request->validate(['confirmar' => ['required', 'accepted']]);

        return DB::transaction(function () use ($request, $nota, $criarNota, $criarOrdemServico) {
            $nota->load('itens');
            if ($nota->itens->isEmpty()) {
                throw ValidationException::withMessages([
                    'nota' => 'Nota sem itens: nao ha o que duplicar.',
                ]);
            }

            $itens = [];
            foreach ($nota->itens as $item) {
                $novoTipo = $item->itemable_type;
                $novoId = (int) $item->itemable_id;

                if ($novoTipo === OrdemServico::class) {
                    // O contrato da Nota proibe reutilizar a MESMA OS.
                    // Cria uma OS nova dentro da mesma transacao, que sera revertida
                    // caso a nova Nota falhe na validacao.
                    $origem = OrdemServico::findOrFail($novoId);
                    $clienteId = (int) ($origem->cliente_id ?: $nota->cliente_id);
                    $veiculoId = (int) ($origem->veiculo_cliente_id ?: $nota->veiculo_cliente_id);
                    if (!$clienteId || !$veiculoId) {
                        throw ValidationException::withMessages([
                            'nota' => 'OS legada sem cliente ou veiculo; corrija o cadastro antes de duplicar.',
                        ]);
                    }
                    $novaOs = $criarOrdemServico->execute([
                        'cliente_id' => $clienteId,
                        'veiculo_cliente_id' => $veiculoId,
                        'setor_servico_id' => $origem->setor_servico_id,
                        'descricao' => $origem->descricao,
                        'valor' => (string) $origem->valor,
                    ], $request->user()->id);
                    $novoId = $novaOs->id;
                } elseif ($novoTipo !== Produto::class) {
                    throw ValidationException::withMessages([
                        'nota' => 'Tipo de item desconhecido; nao foi criada duplicata.',
                    ]);
                }

                $itens[] = [
                    'itemable_type' => $novoTipo,
                    'itemable_id' => $novoId,
                    'descricao' => $item->descricao,
                    'quantidade' => (int) $item->quantidade,
                    'valor_unitario' => (float) $item->valor_unitario,
                    'desconto' => (float) $item->desconto,
                    'garantia_dias' => (int) ($item->garantia_dias ?? 0),
                ];
            }

            $nova = $criarNota->execute([
                'cliente_id' => $nota->cliente_id,
                'veiculo_cliente_id' => $nota->veiculo_cliente_id,
                'km' => $nota->km,
                'km_proxima_troca_oleo' => $nota->km_proxima_troca_oleo,
                'itens' => $itens,
            ], $request->user()->id);

            return redirect()->route('notas.show', $nova)->with('success',
                'Nova Nota aberta criada. OS originais nao foram reutilizadas; pagamentos e anexos nao foram copiados.');
        });
    }

    public function compra(Request $request, Compra $compra)
    {
        $this->validarAdmin($request);
        $compra->load('itens');
        return view('duplicacao.compra', compact('compra'));
    }

    public function duplicarCompra(Request $request, Compra $compra, CriarCompra $criarCompra)
    {
        $this->validarAdmin($request);
        $dados = $request->validate([
            'confirmar' => ['required', 'accepted'],
            'numero_nf' => ['required', 'string', 'max:255'],
        ]);
        $compra->load('itens');
        $nf = trim($dados['numero_nf']);
        if (Compra::query()->where('fornecedor_id', $compra->fornecedor_id)
            ->where('numero_nf', $nf)->where('status', '!=', Compra::STATUS_CANCELADA)->exists()) {
            throw ValidationException::withMessages([
                'numero_nf' => 'Ja existe uma compra nao cancelada com esse fornecedor e numero de NF.',
            ]);
        }
        if ($compra->itens->isEmpty()) {
            throw ValidationException::withMessages(['compra' => 'Compra sem itens.']);
        }

        $nova = $criarCompra->execute([
            'fornecedor_id' => $compra->fornecedor_id,
            'numero_nf' => trim($dados['numero_nf']),
            'serie_nf' => null,
            'chave_nf' => null,
            'data_emissao' => null,
            'data_entrada' => now()->toDateString(),
            'desconto' => $compra->desconto,
            'frete' => $compra->frete,
            'outras_despesas' => $compra->outras_despesas,
            'observacoes' => 'Copia operacional da Compra #' . $compra->id,
            'itens' => $compra->itens->map(fn ($item) => [
                'produto_id' => $item->produto_id,
                'descricao' => $item->descricao,
                'quantidade' => $item->quantidade,
                'valor_unitario' => $item->valor_unitario,
                'desconto' => $item->desconto,
            ])->all(),
        ]);

        return redirect()->route('compras.show', $nova)->with('success',
            'Nova Compra pendente criada. Sem anexos, estoque ou contas a pagar herdados.');
    }
}
