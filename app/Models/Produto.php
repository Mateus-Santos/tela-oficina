<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $casts = [
        'montadora' => 'array',
        'veiculos' => 'array',
        'marcas' => 'array',
        'departamentos' => 'array',
        'valvula' => 'array',
    ];


    protected $fillable = [
        'nome',
        'ano',
        'motor',
        'descricao',
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