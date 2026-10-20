<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CatalogoProduto extends Model
{
    protected $table = 'catalogo_produtos';

    protected $guarded = [];

    protected $casts = [
        'vinculo_confianca' => 'decimal:2',
        'primeira_coleta_em' => 'datetime',
        'ultima_coleta_em' => 'datetime',
    ];

    public function fonte(): BelongsTo
    {
        return $this->belongsTo(CatalogoFonte::class, 'catalogo_fonte_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function estado(): HasOne
    {
        return $this->hasOne(CatalogoProdutoEstado::class, 'catalogo_produto_id');
    }

    public function aplicacoes(): HasMany
    {
        return $this->hasMany(CatalogoProdutoAplicacao::class, 'catalogo_produto_id');
    }

    public function atributos(): HasMany
    {
        return $this->hasMany(CatalogoProdutoAtributo::class, 'catalogo_produto_id');
    }

    public function imagens(): HasMany
    {
        return $this->hasMany(CatalogoProdutoImagem::class, 'catalogo_produto_id');
    }
}
