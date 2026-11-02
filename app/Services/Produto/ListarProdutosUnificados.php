<?php

namespace App\Services\Produto;

use App\Models\CatalogoProduto;
use App\Models\Produto;
use App\Support\Catalogo\PrecoVendaForge;
use App\Services\Produto\ExibirCatalogoProduto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Consulta de leitura: nao materializa catalogo em produtos operacionais. */
class ListarProdutosUnificados
{
    public function paginar(Request $request): LengthAwarePaginator
    {
        $filtros = $request->except('imagem');
        $imagem = $request->input('imagem');

        $operacionais = Produto::query()
            ->selectRaw("'operacional' AS tipo, produtos.id AS registro_id, produtos.nome AS ordenacao")
            ->filtro($filtros);

        if ($imagem === 'com_imagem') {
            $operacionais->where(function ($q) {
                $q->whereHas('imagens')
                    ->orWhereHas('catalogos', function ($c) {
                        $this->comImagemCatalogo($c);
                    });
            });
        } elseif ($imagem === 'sem_imagem') {
            $operacionais->whereDoesntHave('imagens')
                ->whereDoesntHave('catalogos', function ($c) {
                    $this->comImagemCatalogo($c);
                });
        }

        $referencias = CatalogoProduto::query()
            ->leftJoin('catalogo_produto_edicoes AS ed', 'ed.catalogo_produto_id', '=', 'catalogo_produtos.id')
            ->selectRaw("'catalogo' AS tipo, catalogo_produtos.id AS registro_id, COALESCE(NULLIF(ed.nome,''), catalogo_produtos.descricao, '') AS ordenacao")
            ->whereNull('catalogo_produtos.produto_id');

        // Filtros de estoque e status so se aplicam a cadastros fisicos.
        if (($filtros['estoque'] ?? '') !== '' || ($filtros['status'] ?? '') !== '') {
            $referencias->whereRaw('1 = 0');
        }

        if (filled($filtros['codigo'] ?? null)) {
            $referencias->where('catalogo_produtos.chave_externa', (string) $filtros['codigo']);
        }
        if (filled($filtros['codigo_fabricante'] ?? null)) {
            $referencias->where(function ($q) use ($filtros) { $q->where('catalogo_produtos.codigo_fabricante', $filtros['codigo_fabricante'])->orWhere('ed.codigo_fabricante', $filtros['codigo_fabricante']); });
        }
        if (filled($filtros['codigo_barras'] ?? null)) {
            $referencias->where(function ($q) use ($filtros) { $q->where('catalogo_produtos.codigo_barras', $filtros['codigo_barras'])->orWhere('ed.codigo_barras', $filtros['codigo_barras']); });
        }
        if (filled($filtros['marca'] ?? null)) {
            $referencias->where(function ($q) use ($filtros) { $q->where('catalogo_produtos.marca_raw', 'like', '%'.$filtros['marca'].'%')->orWhere('ed.marca', 'like', '%'.$filtros['marca'].'%'); });
        }
        if (filled($filtros['fornecedor_id'] ?? null)) {
            $referencias->whereRaw('1 = 0');
        }

        if (filled($filtros['nome'] ?? null)) {
            foreach (preg_split('/\s+/u', trim((string) $filtros['nome']), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $palavra) {
                $referencias->where(function ($q) use ($palavra) {
                    $q->where('catalogo_produtos.descricao', 'like', '%'.$palavra.'%')
                        ->orWhere('catalogo_produtos.marca_raw', 'like', '%'.$palavra.'%')
                        ->orWhere('catalogo_produtos.codigo_fabricante', 'like', '%'.$palavra.'%')
                        ->orWhere('catalogo_produtos.codigo_barras', 'like', '%'.$palavra.'%')
                        ->orWhere('catalogo_produtos.chave_externa', 'like', '%'.$palavra.'%')
                        ->orWhere('ed.nome', 'like', '%'.$palavra.'%')
                        ->orWhere('ed.descricao', 'like', '%'.$palavra.'%')
                        ->orWhere('ed.marca', 'like', '%'.$palavra.'%')
                        ->orWhere('ed.codigo_fabricante', 'like', '%'.$palavra.'%');
                });
            }
        }
        if ($imagem === 'com_imagem') {
            $this->comImagemCatalogo($referencias);
        } elseif ($imagem === 'sem_imagem') {
            $referencias->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('imagem_thumb_url')->orWhere('imagem_thumb_url', '');
                })->where(function ($q) {
                    $q->whereNull('imagem_url')->orWhere('imagem_url', '');
                })->whereDoesntHave('imagens', function ($q) {
                    $q->whereNotNull('url_original')->orWhereNotNull('url_thumb');
                });
            });
        }

        $resultado = DB::query()
            ->fromSub($operacionais->toBase()->unionAll($referencias->toBase()), 'itens')
            ->orderBy('ordenacao')
            ->orderBy('tipo')
            ->orderBy('registro_id')
            ->paginate(20)
            ->withQueryString();

        $linhas = $resultado->getCollection();
        $idsOperacionais = $linhas->where('tipo', 'operacional')->pluck('registro_id');
        $idsCatalogo = $linhas->where('tipo', 'catalogo')->pluck('registro_id');

        $produtos = Produto::query()
            ->with(['marcaRelacionada', 'imagens', 'catalogos.estado', 'catalogos.imagens'])
            ->whereIn('id', $idsOperacionais)->get()->keyBy('id');
        $catalogos = CatalogoProduto::query()
            ->with(['estado', 'imagens', 'edicao'])
            ->whereIn('id', $idsCatalogo)->get()->keyBy('id');

        $resultado->setCollection($linhas->map(function ($linha) use ($produtos, $catalogos) {
            if ($linha->tipo === 'operacional') {
                $produto = $produtos->get($linha->registro_id);
                if (! $produto) {
                    return null;
                }
                $sugerido = null;
                $imagemCatalogo = null;
                foreach ($produto->catalogos as $catalogo) {
                    $sugerido = PrecoVendaForge::maior(
                        $sugerido,
                        PrecoVendaForge::arredondar($catalogo->estado?->preco_venda_sugerido)
                    );
                    $imagemCatalogo ??= $this->imagemCatalogo($catalogo);
                }
                $local = $produto->imagens->first()?->caminho;

                return (object) [
                    'nome' => $produto->nome,
                    'descricao' => $produto->descricao,
                    'marca' => $produto->marcaRelacionada?->nome,
                    'id' => $produto->id,
                    'status' => $produto->status,
                    'codigo' => $produto->codigo,
                    'referencia' => $produto->codigo_fabricante,
                    'codigo_barras' => $produto->codigo_barras,
                    'imagem' => $local ? asset('storage/'.$local) : $imagemCatalogo,
                    'preco' => $produto->preco_uni,
                    'sugerido' => $sugerido,
                    'catalogo_id' => $produto->catalogos->first()?->id,
                    'quantidade' => $produto->quantidade,
                    'url' => route('produtos.show', $produto),
                    'operacional' => true,
                ];
            }

            $catalogo = $catalogos->get($linha->registro_id);
            if (! $catalogo) {
                return null;
            }
            $dados = app(ExibirCatalogoProduto::class)->dados($catalogo);

            return (object) [
                'nome' => $dados['nome'],
                'descricao' => $dados['descricao'],
                'marca' => $dados['marca'],
                'id' => null,
                'status' => null,
                'codigo' => null,
                'referencia' => $dados['referencia'],
                'codigo_barras' => $dados['codigo_barras'],
                'imagem' => $dados['imagem'],
                'preco' => $dados['preco'],
                'sugerido' => $dados['sugerido'],
                'quantidade' => null,
                'url' => route('produtos.catalogo.show', $catalogo),
                'operacional' => false,
                'catalogo_id' => $catalogo->id,
            ];
        })->filter()->values());

        return $resultado;
    }

    private function comImagemCatalogo($query): void
    {
        $query->where(function ($q) {
            $q->whereNotNull('imagem_thumb_url')->where('imagem_thumb_url', '<>', '')
                ->orWhere(function ($q) {
                    $q->whereNotNull('imagem_url')->where('imagem_url', '<>', '');
                })->orWhereHas('imagens', function ($q) {
                    $q->whereNotNull('url_thumb')->orWhereNotNull('url_original');
                });
        });
    }

    private function imagemCatalogo(CatalogoProduto $catalogo): ?string
    {
        $imagem = $catalogo->imagens->sortByDesc('principal')->first();
        return $imagem?->url_thumb ?: $imagem?->url_original
            ?: $catalogo->imagem_thumb_url ?: $catalogo->imagem_url;
    }
}
