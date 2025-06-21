<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_barras',
        'montadora',
        'nome',
        'ano_modelo',
        'veiculos',
        'motor',
        'descricao',
        'marcas',
        'departamentos',
        'valvula',
        'quantidade',
        'estoque_minimo',
        'preco_uni',
        'img',
        'codigo_fabricante',
        'localizacao',
        'unidade_medida',
        'status',
        'fornecedor_id',
    ];

    protected $casts = [
        'montadora'     => 'array',
        'veiculos'      => 'array',
        'marcas'        => 'array',
        'departamentos' => 'array',
        'valvula'       => 'array',
        'status'        => 'boolean',
        'quantidade'    => 'integer',
        'estoque_minimo'=> 'integer',
        'preco_uni'     => 'float',
        'fornecedor_id' => 'integer',
        'ano_modelo'    => 'integer',
    ];

    public function scopeFiltro($query, $filtros)
    {
        if (!empty($filtros['nome'])) {
            $query->where('nome', 'like', '%' . $filtros['nome'] . '%');
        }

        if (!empty($filtros['codigo_barras'])) {
            $query->where('codigo_barras', $filtros['codigo_barras']);
        }

        if (!empty($filtros['ano_modelo'])) {
            $query->where('ano_modelo', $filtros['ano_modelo']);
        }

        if (!empty($filtros['montadora'])) {
            $query->whereJsonContains('montadora', $filtros['montadora']);
        }

        if (!empty($filtros['motor'])) {
            $query->where('motor', $filtros['motor']);
        }

        if (!empty($filtros['marcas'])) {
            $query->whereJsonContains('marcas', $filtros['marcas']);
        }

        if (!empty($filtros['departamentos'])) {
            $query->whereJsonContains('departamentos', $filtros['departamentos']);
        }

        if (!empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        if (!empty($filtros['fornecedor_id'])) {
            $query->where('fornecedor_id', $filtros['fornecedor_id']);
        }

        return $query;
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function produtoVendas()
    {
        return $this->hasMany(ProdutoVenda::class);
    }
}
