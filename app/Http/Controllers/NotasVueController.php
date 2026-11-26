<?php

namespace App\Http\Controllers;

use App\Actions\Notas\AlterarEtapaNota;
use App\Actions\Notas\AtualizarNota;
use App\Actions\Notas\CancelarNota;
use App\Actions\Notas\CriarNota;
use App\Actions\Notas\FinalizarNota;
use App\Services\Nota\MoverEtapaEmLote;
use App\Http\Requests\FinalizarNotaRequest;
use App\Http\Requests\Notas\StoreNotaRequest;
use App\Http\Requests\Notas\UpdateNotaRequest;
use App\Models\CategoriaFinanceira;
use App\Models\Cliente;
use App\Models\Etapa;
use App\Models\Nota;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\SetorServico;
use App\Models\VeiculosCliente;
use App\Support\Feux\WhatsappContato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * API JSON para a Central Vue. As mutacoes delegam as Actions ja homologadas.
 * Grupo admin/auth/check.blocked fica em routes/web.php. Nenhuma regra financeira
 * ou de estoque e reimplementada no navegador.
 */
final class NotasVueController extends Controller
{
    public function shell(): View
    {
        return view('notas_item.vue.index');
    }

    public function dados(Request $request): JsonResponse
    {
        $f = $request->validate([
            'status' => ['nullable', 'in:Aberto,Finalizado,Cancelado,Todos'],
            'etapa_id' => ['nullable', 'integer', 'exists:etapas,id'],
            'cliente' => ['nullable', 'string', 'max:120'],
            'placa' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:120'],
            'item_tipo' => ['nullable', 'in:todos,pecas,servicos,mistas,sem_itens'],
            'pagina' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'incluir_canceladas' => ['nullable', 'boolean'],
        ]);
        $f['status'] = $f['status'] ?? 'Aberto';
        $f['item_tipo'] = $f['item_tipo'] ?? 'todos';
        $base = Nota::query()->filtro($f);
        // Regra FEUX L03L: 'Todas' exclui canceladas por padrão.
        // A aba 'Canceladas' SEMPRE mostra exclusivamente canceladas.
        // O mesmo filtro base alimenta cartões, indicadores e paginação.
        if ($f['status'] === 'Todos' && ! $request->boolean('incluir_canceladas')) {
            $base->where('notas.status', '!=', 'Cancelado');
        }
        if (! empty($f['q'])) {
            $q = trim($f['q']);
            $base->where(function (Builder $query) use ($q) {
                if (ctype_digit($q)) {
                    $query->orWhereKey((int) $q);
                }
                $query->orWhereHas('cliente.pessoa', fn (Builder $b) =>
                    $b->where('nome', 'like', '%'.$q.'%'));
                $query->orWhereHas('veiculosCliente', fn (Builder $b) =>
                    $b->where('placa', 'like', '%'.$q.'%'));
            });
        }
        $tipo = $f['item_tipo'];
        if (in_array($tipo, ['pecas', 'mistas'], true)) {
            $base->whereHas('itens', fn (Builder $q) => $q->where('itemable_type', Produto::class));
        }
        if (in_array($tipo, ['servicos', 'mistas'], true)) {
            $base->whereHas('itens', fn (Builder $q) => $q->where('itemable_type', OrdemServico::class));
        }
        if ($tipo === 'sem_itens') {
            $base->whereDoesntHave('itens');
        }
        $total = (clone $base)->count();
        $clientes = (clone $base)->whereNotNull('cliente_id')->distinct()->count('cliente_id');
        $valor = (float) (clone $base)->sum('total');
        $tipos = DB::table('notas_itens as i')
            ->joinSub((clone $base)->select('notas.id'), 'filtradas', 'filtradas.id', '=', 'i.nota_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN i.itemable_type = ? THEN i.valor_total ELSE 0 END), 0) as pecas', [Produto::class])
            ->selectRaw('COALESCE(SUM(CASE WHEN i.itemable_type = ? THEN i.valor_total ELSE 0 END), 0) as servicos', [OrdemServico::class])
            ->first();
        $semEstoque = (clone $base)->where('status', 'Aberto')
            ->whereHas('itens', function (Builder $q) {
                $q->where('itemable_type', Produto::class)
                    ->whereExists(function ($sub) {
                        $sub->selectRaw('1')->from('produtos')
                            ->whereColumn('produtos.id', 'notas_itens.itemable_id')
                            ->where(fn ($s) => $s->where('produtos.quantidade', '<=', 0)
                                ->orWhereColumn('produtos.quantidade', '<', 'notas_itens.quantidade'));
                    });
            })->count();
        // Etapa final so conta Notas efetivamente finalizadas.
        $etapaFinal = (clone $base)->whereIn('status', ['Finalizado','Concluido'])
            ->whereHas('etapa', fn (Builder $q) => $q->where('tipo', 'final'))->count();

        // Pagina por lote para evitar trafego ilimitado. A UI usa 'Carregar mais'.
        $page = (int) ($f['pagina'] ?? 1);
        $limit = 60;
        $notas = (clone $base)->with([
            'cliente.pessoa', 'veiculosCliente.veiculo.montadora', 'etapa', 'itens.itemable',
        ])->orderByDesc('notas.created_at')->orderByDesc('notas.id')
            ->forPage($page, $limit)->get();
        return response()->json([
            'data' => $notas->map(fn (Nota $n) => $this->resumo($n))->values(),
            'etapas' => Etapa::query()->paraNota()->ativas()
                ->get(['id', 'nome', 'cor', 'tipo', 'ordem']),
            'metricas' => [
                'notas' => $total, 'clientes' => $clientes, 'valor_notas' => $valor,
                'pecas' => (float) ($tipos->pecas ?? 0),
                'servicos' => (float) ($tipos->servicos ?? 0),
                'sem_estoque' => $semEstoque, 'etapa_final' => $etapaFinal,
            ],
            'paginacao' => ['pagina' => $page, 'limite' => $limit,
                'total' => $total, 'possui_mais' => $page * $limit < $total],
        ]);
    }

    private function resumo(Nota $nota): array
    {
        $pessoa = $nota->cliente?->pessoa;
        $telefone = $pessoa?->telefone_1 ?: $pessoa?->telefone_2;
        $veiculo = $nota->veiculosCliente;
        $itens = $nota->itens;
        $pecas = $itens->where('itemable_type', Produto::class);
        $servicos = $itens->where('itemable_type', OrdemServico::class);
        $semEstoque = $nota->status === 'Aberto' && $pecas->contains(fn ($i) =>
            ! $i->itemable || (float) $i->itemable->quantidade < (float) $i->quantidade);
        return [
            'id' => $nota->id, 'status' => $nota->status,
            'etapa_id' => $nota->etapa_id, 'etapa' => $nota->etapa?->nome,
            'total' => (float) $nota->total,
            'cliente_id' => $nota->cliente_id, 'cliente' => $pessoa?->nome ?: 'Cliente Geral / Balcão',
            'telefone' => $telefone, 'whatsapp' => WhatsappContato::url($telefone),
            'veiculo_cliente_id' => $nota->veiculo_cliente_id,
            'veiculo' => $veiculo?->veiculo?->nome ?? $veiculo?->veiculo?->modelo,
            'placa' => $veiculo?->placa,
            'criada_em' => $nota->created_at?->format('d/m/Y H:i'),
            'pecas' => $pecas->count(), 'servicos' => $servicos->count(),
            'sem_estoque' => $semEstoque,
        ];
    }

    public function detalhe(Nota $nota): JsonResponse
    {
        $nota->load([
            'cliente.pessoa', 'veiculosCliente.veiculo.montadora', 'etapa',
            'itens.itemable', 'contaReceber.parcelas', 'contaReceber.recebimentosAtivos',
        ]);
        $data = $this->resumo($nota);
        $data['km'] = $nota->km;
        $data['km_proxima_troca_oleo'] = $nota->km_proxima_troca_oleo;
        $data['tipo'] = $nota->tipo;
        $data['observacoes'] = $nota->observacoes;
        $data['subtotal'] = (float) $nota->subtotal;
        $data['desconto'] = (float) $nota->desconto;
        $data['itens'] = $nota->itens->map(fn ($i) => [
            'id' => $i->id,
            'itemable_type' => $i->itemable_type,
            'itemable_id' => $i->itemable_id,
            'descricao' => $i->descricao,
            'quantidade' => (int) $i->quantidade,
            'valor_unitario' => (float) $i->valor_unitario,
            'desconto' => (float) $i->desconto,
            'valor_total' => (float) $i->valor_total,
            'garantia_dias' => $i->garantia_dias,
            'estoque_atual' => $i->itemable_type === Produto::class
                ? (float) ($i->itemable?->quantidade ?? 0) : null,
        ])->values();
        $conta = $nota->contaReceber;
        $data['conta_receber'] = $conta ? [
            'id' => $conta->id, 'status' => $conta->status,
            'valor_original' => (float) $conta->valor_original,
            'recebido' => (float) $conta->recebimentosAtivos->sum('valor'),
            'parcelas' => $conta->parcelas->map(fn ($p) => [
                'numero' => $p->numero, 'valor' => (float) $p->valor,
                'data_vencimento' => $p->data_vencimento,
            ])->values(),
        ] : null;
        $data['pode_editar'] = $nota->status === 'Aberto';
        $data['pode_finalizar'] = $nota->status === 'Aberto';
        $data['pode_cancelar'] = in_array($nota->status, ['Aberto', 'Finalizado', 'Concluido'], true);
        $data['pode_excluir'] = $nota->status === 'Aberto'
            && ! $conta
            && ! $nota->itens->contains(fn ($i) => $i->itemable_type === OrdemServico::class)
            && ! OrdemServico::query()->where('nota_id', $nota->id)->exists();
        $data['urls'] = [
            'pdf' => route('notas.pdf', $nota),
            'pdf_download' => route('notas.pdf.download', $nota),
            'pdf_interno' => route('notas.pdf.interno.download', $nota),
            'conta' => $conta ? route('contas-receber.show', $conta) : null,
            'show_legado' => route('notas.show', $nota),
        ];
        return response()->json(['data' => $data]);
    }

    public function opcoes(Request $request): JsonResponse
    {
        $f = $request->validate([
            'tipo' => ['required', 'in:clientes,veiculos,produtos,os,setores,categorias'],
            'q' => ['nullable', 'string', 'max:100'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'veiculo_cliente_id' => ['nullable', 'integer', 'exists:veiculos_clientes,id'],
        ]);
        $q = trim($f['q'] ?? '');
        $tipo = $f['tipo'];
        if ($tipo === 'clientes') {
            if (mb_strlen($q) < 2) return response()->json(['data' => []]);
            $data = Cliente::query()->with('pessoa')->whereHas('pessoa', fn (Builder $b) =>
                $b->where('nome', 'like', '%'.$q.'%'))->limit(15)->get()
                ->map(fn ($c) => ['id' => $c->id,
                    'nome' => $c->pessoa?->nome, 'telefone' => $c->pessoa?->telefone_1]);
        } elseif ($tipo === 'veiculos') {
            if (empty($f['cliente_id'])) return response()->json(['data' => []]);
            $data = VeiculosCliente::query()->with('veiculo')
                ->whereHas('clientes', fn (Builder $b) => $b->whereKey((int) $f['cliente_id']))
                ->limit(60)->get()->map(fn ($v) => [
                    'id' => $v->id, 'placa' => $v->placa,
                    'nome' => $v->veiculo?->nome ?? $v->veiculo?->modelo ?? 'Veículo',
                ]);
        } elseif ($tipo === 'produtos') {
            if (mb_strlen($q) < 2) return response()->json(['data' => []]);
            $data = Produto::query()->where('status', true)
                ->where(fn (Builder $b) => $b->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('codigo', 'like', '%'.$q.'%')
                    ->orWhere('codigo_fabricante', 'like', '%'.$q.'%')
                    ->orWhere('codigo_barras', 'like', '%'.$q.'%'))
                ->limit(15)->get()->map(fn ($p) => [
                    'id' => $p->id, 'nome' => $p->nome, 'descricao' => $p->descricao,
                    'preco' => (float) $p->preco_uni, 'estoque' => (float) $p->quantidade,
                    'codigo' => $p->codigo,
                ]);
        } elseif ($tipo === 'os') {
            if (empty($f['cliente_id']) || empty($f['veiculo_cliente_id']))
                return response()->json(['data' => []]);
            $data = OrdemServico::query()
                ->where('cliente_id', $f['cliente_id'])
                ->where('veiculo_cliente_id', $f['veiculo_cliente_id'])
                ->whereNotExists(function ($sub) {
                    $sub->selectRaw('1')->from('notas_itens as ni')
                        ->where('ni.itemable_type', OrdemServico::class)
                        ->whereColumn('ni.itemable_id', 'ordem_servicos.id');
                })
                ->limit(15)->get()->map(fn ($os) => [
                    'id' => $os->id, 'descricao' => $os->descricao,
                    'valor' => (float) $os->valor, 'status' => $os->status,
                ]);
        } elseif ($tipo === 'setores') {
            $data = SetorServico::query()->orderBy('setor')->get(['id', 'setor'])
                ->map(fn ($s) => ['id' => $s->id, 'nome' => $s->setor]);
        } else {
            $data = CategoriaFinanceira::query()->where('tipo', 'entrada')
                ->where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
        }
        return response()->json(['data' => $data]);
    }

    public function criar(StoreNotaRequest $request, CriarNota $acao): JsonResponse
    {
        $nota = $acao->execute($request->validated(), $request->user()->id);
        return response()->json(['message' => 'Nota criada com sucesso.', 'id' => $nota->id], 201);
    }

    public function editar(UpdateNotaRequest $request, Nota $nota, AtualizarNota $acao): JsonResponse
    {
        $nota = $acao->execute($nota, $request->validated());
        return response()->json(['message' => 'Nota atualizada.', 'id' => $nota->id]);
    }

    public function previsualizarLote(Request $request, MoverEtapaEmLote $servico): JsonResponse
    {
        $dados = $request->validate([
            'origem_etapa_id' => ['required','integer','exists:etapas,id'],
            'destino_etapa_id' => ['required','integer','exists:etapas,id'],
        ]);
        try {
            return response()->json($servico->previsualizar((int) $dados['origem_etapa_id'], (int) $dados['destino_etapa_id']));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function moverLote(Request $request, MoverEtapaEmLote $servico): JsonResponse
    {
        $dados = $request->validate([
            'origem_etapa_id' => ['required','integer','exists:etapas,id'],
            'destino_etapa_id' => ['required','integer','exists:etapas,id'],
            'assinatura' => ['required','string','size:64'],
            'confirmacao' => ['required','in:MOVER_ETAPA_LOTE'],
        ]);
        try {
            $result = $servico->executar((int) $dados['origem_etapa_id'],
                (int) $dados['destino_etapa_id'], $dados['assinatura'], $request->user()->id);
            return response()->json(['message' => "{$result['movidas']} Notas movidas com histórico."] + $result);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function mover(Request $request, Nota $nota, AlterarEtapaNota $acao): JsonResponse
    {
        $d = $request->validate(['etapa_id' => ['required', 'integer', 'exists:etapas,id']]);
        try {
            $nova = $acao->execute($nota, Etapa::findOrFail($d['etapa_id']),
                $request->user()->id, 'Movimentação pela Central Vue.');
            return response()->json(['message' => 'Etapa atualizada.', 'id' => $nova->id]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function finalizar(FinalizarNotaRequest $request, Nota $nota, FinalizarNota $acao): JsonResponse
    {
        try {
            $acao->execute($nota, $request->validated(), $request->user()->id);
            return response()->json(['message' => 'Nota finalizada com sucesso.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancelar(Nota $nota, CancelarNota $acao): JsonResponse
    {
        try {
            $acao->execute($nota);
            return response()->json(['message' => 'Nota cancelada com sucesso.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function excluir(Nota $nota): JsonResponse
    {
        try {
            DB::transaction(function () use ($nota) {
                $locked = Nota::query()->lockForUpdate()->findOrFail($nota->id);
                if ($locked->status !== 'Aberto') {
                    throw ValidationException::withMessages(['nota' => 'Somente Notas abertas podem ser excluídas.']);
                }
                if ($locked->itens()->where('itemable_type', OrdemServico::class)->exists()
                    || OrdemServico::query()->where('nota_id', $locked->id)->exists()
                    || $locked->contaReceber()->exists()) {
                    throw ValidationException::withMessages(['nota' => 'Nota com O.S. ou financeiro vinculado não pode ser excluída.']);
                }
                $locked->delete();
            });
            return response()->json(['message' => 'Nota excluída.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
