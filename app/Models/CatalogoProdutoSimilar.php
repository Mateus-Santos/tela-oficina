<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogoProdutoSimilar extends Model
{
    protected $table = 'catalogo_produto_similares';
    protected $guarded = [];

    public function similar(): BelongsTo
    {
        return $this->belongsTo(CatalogoProduto::class, 'similar_catalogo_produto_id');
    }
}
