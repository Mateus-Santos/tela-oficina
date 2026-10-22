<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogoFonte extends Model
{
    protected $table = 'catalogo_fontes';

    protected $fillable = [
        'codigo',
        'nome',
        'tipo',
        'ativo',
        'configuracao',
        'ultima_importacao_em',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'configuracao' => 'array',
        'ultima_importacao_em' => 'datetime',
    ];

    public function produtos(): HasMany
    {
        return $this->hasMany(CatalogoProduto::class, 'catalogo_fonte_id');
    }
}
