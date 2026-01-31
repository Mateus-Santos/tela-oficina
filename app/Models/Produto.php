<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
        'quantidade',
        'estoque_minimo',
        'preco_uni',
        'img',
        'codigo_fabricante',
        'codigo_barras',
        'status',
        'fornecedor_id',
        'marca',
    ];

    public function scopeFiltro($query, $filtros)
    {
        if (!empty($filtros['fornecedor_id'])) {
            $query->where('fornecedor_id', $filtros['fornecedor_id']);
        }

        return $query;
    }

    public function veiculos()
    {
        return $this->belongsToMany(Veiculo::class, 'produtos_veiculos');
    }

    public function produtoVendas(): HasMany
    {
        return $this->hasMany(ProdutoVenda::class);
    }
}
