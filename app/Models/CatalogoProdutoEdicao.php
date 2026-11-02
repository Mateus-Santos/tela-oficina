<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Edicoes humanas separadas dos dados coletados: nunca importadas pelo Forge. */
class CatalogoProdutoEdicao extends Model
{
    protected $table = 'catalogo_produto_edicoes';
    protected $fillable = [
        'nome', 'descricao', 'marca', 'codigo_fabricante',
        'codigo_barras', 'preco_venda',
    ];
    protected $casts = ['preco_venda' => 'decimal:2'];

    public function catalogoProduto(): BelongsTo
    {
        return $this->belongsTo(CatalogoProduto::class, 'catalogo_produto_id');
    }
}
