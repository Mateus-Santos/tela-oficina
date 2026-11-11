<?php

namespace App\Services\PortalCliente;

use App\Models\Produto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListarProdutosDisponiveis
{
    public function paginar(
        string $busca = ''
    ): LengthAwarePaginator {
        $busca = trim($busca);

        // Exclusivamente produtos operacionais.
        // Não consulta as referências externas isoladas.
        $query = Produto::query()
            ->select([
                'id',
                'nome',
                'marca_id',
                'preco_uni',
            ])
            ->with([
                'marcaRelacionada:id,nome',
            ])
            ->where('status', true)
            ->where('quantidade', '>', 0);

        if ($busca !== '') {
            $termo = '%'.$busca.'%';

            $query->where(
                function (Builder $filtro) use ($termo) {
                    $filtro->where(
                        'nome',
                        'like',
                        $termo
                    )->orWhereHas(
                        'marcaRelacionada',
                        function (Builder $marca) use ($termo) {
                            $marca->where(
                                'nome',
                                'like',
                                $termo
                            );
                        }
                    );
                }
            );
        }

        return $query
            ->orderBy('nome')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();
    }
}
