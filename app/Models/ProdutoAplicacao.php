<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoAplicacao extends Model
{
    protected $table =
        'produto_aplicacoes';

    public const ORIGEM_MANUAL =
        'manual';

    public const STATUS_RESOLVIDA =
        'resolvida';

    public const STATUS_REVISAO =
        'revisao';

    protected $fillable = [
        'produto_id',
        'veiculo_id',
        'catalogo_produto_aplicacao_id',
        'chave_aplicacao',
        'mes_ano',
        'modelo',
        'motor',
        'comp',
        'valvulas',
        'cilindros',
        'combustivel',
        'complemento',
        'origem',
        'status_resolucao',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(
            Produto::class
        );
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(
            Veiculo::class
        );
    }

    public function catalogoAplicacao(): BelongsTo
    {
        return $this->belongsTo(
            CatalogoProdutoAplicacao::class,
            'catalogo_produto_aplicacao_id'
        );
    }
}
