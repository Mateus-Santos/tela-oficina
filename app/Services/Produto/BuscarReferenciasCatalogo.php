<?php

namespace App\Services\Produto;

use App\Models\CatalogoProduto;

/** Referencias que ainda nao possuem produto operacional. Pesquisa nao cadastra nem movimenta estoque. */
final class BuscarReferenciasCatalogo
{
    public function buscar(string $busca, int $limite = 8): array
    {
        $palavras = preg_split('/\s+/u', trim($busca), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($palavras === []) {
            return [];
        }

        $query = CatalogoProduto::query()
            ->whereNull('produto_id')
            ->with(['edicao', 'estado', 'imagens']);

        foreach ($palavras as $palavra) {
            $query->where(function ($q) use ($palavra) {
                $q->where('descricao', 'like', '%'.$palavra.'%')
                    ->orWhere('marca_raw', 'like', '%'.$palavra.'%')
                    ->orWhere('codigo_fabricante', 'like', '%'.$palavra.'%')
                    ->orWhere('codigo_barras', 'like', '%'.$palavra.'%')
                    ->orWhere('chave_externa', 'like', '%'.$palavra.'%')
                    ->orWhereHas('edicao', function ($edicao) use ($palavra) {
                        $edicao->where('nome', 'like', '%'.$palavra.'%')
                            ->orWhere('marca', 'like', '%'.$palavra.'%')
                            ->orWhere('codigo_fabricante', 'like', '%'.$palavra.'%')
                            ->orWhere('codigo_barras', 'like', '%'.$palavra.'%');
                    });
            });
        }

        $exibicao = app(ExibirCatalogoProduto::class);
        return $query->orderBy('descricao')->limit(max(1, min($limite, 15)))
            ->get()->map(function (CatalogoProduto $catalogo) use ($exibicao) {
                $dados = $exibicao->dados($catalogo);
                return [
                    'id' => null, // NUNCA usar catalogo_id em campos produto_id/ itemable_id.
                    'produto_id' => null,
                    'catalogo_id' => $catalogo->id,
                    'tipo_resultado' => 'referencia_catalogo',
                    'selecionavel_em_nota' => false,
                    'selecionavel_em_compra' => false,
                    'nome' => $dados['nome'],
                    'descricao' => $dados['descricao'],
                    'marca' => $dados['marca'],
                    'codigo' => null,
                    'codigo_fabricante' => $dados['referencia'],
                    'codigo_barras' => $dados['codigo_barras'],
                    'preco' => $dados['preco'] !== null ? (float) $dados['preco'] : null,
                    'preco_sugerido' => $dados['sugerido'] !== null ? (float) $dados['sugerido'] : null,
                    'imagem_url' => $dados['imagem'],
                    'estoque_atual' => null,
                    'estoque_minimo' => null,
                    'disponivel' => false,
                    'url' => route('produtos.catalogo.show', $catalogo),
                    'acao' => 'Habilitar no cadastro antes de utilizar',
                ];
            })->values()->all();
    }
}
