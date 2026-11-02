<?php

namespace App\Http\Controllers;

use App\Models\CatalogoProduto;
use App\Models\Marca;
use App\Models\Produto;
use App\Services\Produto\ExibirCatalogoProduto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Catalogo pode ser editado sem tocar em Produto, estoque, notas ou compras. */
class CatalogoProdutoComplementoController extends Controller
{
    private function exigirAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    public function edit(Request $request, CatalogoProduto $catalogo, ExibirCatalogoProduto $exibicao)
    {
        $this->exigirAdmin($request);
        if ($catalogo->produto_id !== null) {
            return redirect()->route('produtos.edit', $catalogo->produto_id);
        }
        $catalogo->load(['edicao', 'estado', 'imagens']);
        $dados = $exibicao->dados($catalogo);
        return view('produto.editarcatalogo', compact('catalogo', 'dados'));
    }

    public function update(Request $request, CatalogoProduto $catalogo)
    {
        $this->exigirAdmin($request);
        abort_if($catalogo->produto_id !== null, 409, 'Produto vinculado: edite o cadastro operacional.');
        $dados = $request->validate([
            'nome' => ['nullable', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:4000'],
            'marca' => ['nullable', 'string', 'max:150'],
            'codigo_fabricante' => ['nullable', 'string', 'max:191'],
            'codigo_barras' => ['nullable', 'string', 'max:191'],
            'preco_venda' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);
        foreach ($dados as $campo => $valor) {
            if (is_string($valor)) {
                $dados[$campo] = trim($valor) === '' ? null : trim($valor);
            }
        }
        $catalogo->edicao()->updateOrCreate([], $dados);
        return redirect()->route('produtos.catalogo.show', $catalogo)
            ->with('success', 'Personalizações salvas. Importações futuras não alteram estes campos.');
    }

    /** Acao deliberada. Jamais executada no GET de uma pesquisa. Estoque sempre zero. */
    public function habilitar(Request $request, CatalogoProduto $catalogo, ExibirCatalogoProduto $exibicao)
    {
        $this->exigirAdmin($request);
        $produto = DB::transaction(function () use ($catalogo, $exibicao) {
            $catalogo = CatalogoProduto::query()->lockForUpdate()->findOrFail($catalogo->id);
            if ($catalogo->produto_id) {
                return Produto::query()->findOrFail($catalogo->produto_id);
            }
            $catalogo->load(['edicao', 'estado', 'imagens']);
            $dados = $exibicao->dados($catalogo);
            $marcaNome = trim((string) ($dados['marca'] ?: 'SEM MARCA'));
            $marca = Marca::query()->whereRaw('LOWER(nome) = ?', [mb_strtolower($marcaNome)])->first();
            $marca ??= Marca::query()->firstOrCreate(['nome' => $marcaNome], ['ativo' => true]);

            $referencia = trim((string) ($dados['referencia'] ?: 'SKY-'.$catalogo->chave_externa));
            if (Produto::query()->where('marca_id', $marca->id)
                ->where('codigo_fabricante', $referencia)->exists()) {
                throw ValidationException::withMessages([
                    'catalogo' => 'Ja existe um produto operacional desta marca e referencia. Revise o vinculo antes de cadastrar.',
                ]);
            }
            $ean = trim((string) ($dados['codigo_barras'] ?? ''));
            if ($ean !== '' && Produto::query()->where('codigo_barras', $ean)->exists()) {
                // Nunca inventar identidade usando EAN duplicado.
                throw ValidationException::withMessages([
                    'catalogo' => 'Codigo de barras ja cadastrado. Vincule manualmente ao produto correto.',
                ]);
            }
            $preco = $dados['preco'];
            $produto = Produto::query()->create([
                'codigo' => null,
                'nome' => mb_substr((string) $dados['nome'], 0, 150),
                'descricao' => $dados['descricao'] ?: $dados['nome'],
                'marca_id' => $marca->id,
                'codigo_fabricante' => $referencia,
                'codigo_barras' => $ean !== '' ? $ean : null,
                'ncm' => $catalogo->ncm,
                'preco_uni' => $preco !== null ? (float) $preco : 0,
                'quantidade' => '0.000',
                'estoque_minimo' => '0.000',
                'status' => true,
            ]);
            $catalogo->forceFill([
                'produto_id' => $produto->id,
                'vinculo_status' => 'manual',
                'vinculo_motivo' => 'Cadastro operacional autorizado pelo administrador',
            ])->save();
            return $produto;
        });

        return redirect()->route('produtos.edit', $produto)
            ->with('success', 'Produto habilitado com codigo interno. Estoque inicial zero. Confira os dados antes da compra ou venda.');
    }

    public function similares(CatalogoProduto $catalogo, ExibirCatalogoProduto $exibicao)
    {
        // A Sky indica proximidade comercial; equivalencia tecnica NAO homologada.
        $relacoes = $catalogo->similares()->with(['similar.estado', 'similar.imagens', 'similar.edicao'])
            ->orderBy('id')->limit(31)->get();
        $similares = $relacoes->take(30)->map(function ($relacao) use ($exibicao) {
            $produto = $relacao->similar;
            if (! $produto) return null;
            return [
                'dados' => $exibicao->dados($produto),
                'url' => $produto->produto_id
                    ? route('produtos.show', $produto->produto_id)
                    : route('produtos.catalogo.show', $produto),
            ];
        })->filter()->values();
        $mais = $relacoes->count() > 30;
        return view('produto.partials.similares-lista', compact('similares', 'mais'));
    }
}
