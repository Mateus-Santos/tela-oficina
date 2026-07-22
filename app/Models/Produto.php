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

    public function scopeFiltro($query, array $filtros)
    {
        return $query
            ->when($filtros['nome'] ?? null, fn ($q, $v) =>
                $q->where('nome', 'like', "%{$v}%")
            )
            ->when($filtros['codigo_barras'] ?? null, fn ($q, $v) =>
                $q->where('codigo_barras', $v)
            )
            ->when($filtros['codigo_fabricante'] ?? null, fn ($q, $v) =>
                $q->where('codigo_fabricante', 'like', "%{$v}%")
            )
            ->when($filtros['fornecedor_id'] ?? null, fn ($q, $v) =>
                $q->where('fornecedor_id', $v)
            );
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
