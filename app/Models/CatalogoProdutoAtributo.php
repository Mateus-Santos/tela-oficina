<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogoProdutoAtributo extends Model
{
    protected $table = 'catalogo_produto_atributos';

    protected $guarded = [];

    protected $casts = [
        'primeira_coleta_em' => 'datetime',
        'ultima_confirmacao_em' => 'datetime',
    ];

    public function catalogoProduto(): BelongsTo
    {
        return $this->belongsTo(CatalogoProduto::class, 'catalogo_produto_id');
    }
}
