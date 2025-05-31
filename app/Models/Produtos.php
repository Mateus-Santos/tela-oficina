<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produtos extends Model
{
    use HasFactory;

    protected $fillable = [
        'montadora',
        'nome',
        'ano',
        'veiculos',
        'motor',
        'descricao',
        'marcas',
        'departamentos',
        'valvula',
        'quantidade',
        'preco_uni',
        'img',
        'codigo_fabricante',
    ];

    public function ProdutoVenda(): hasMany
    {
        return $this->hasMany(ProdutoVenda::class);
    }
    
}