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

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function produtoVendas()
    {
        return $this->hasMany(ProdutoVenda::class);
    }
}
