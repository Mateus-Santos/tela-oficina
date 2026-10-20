<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogoProdutoEstado extends Model
{
    protected $table = 'catalogo_produto_estados';

    protected $guarded = [];

    protected $casts = [
        'custo_observado' => 'decimal:4',
        'preco_venda_observado' => 'decimal:4',
        'custo_base_seguro' => 'decimal:4',
        'promocao_detectada' => 'boolean',
        'primeira_coleta_em' => 'datetime',
        'ultima_coleta_em' => 'datetime',
    ];

    public function catalogoProduto(): BelongsTo
    {
        return $this->belongsTo(CatalogoProduto::class, 'catalogo_produto_id');
    }
}
