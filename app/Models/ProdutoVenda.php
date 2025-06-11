<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;



class produtoVenda extends Model
{

    use HasFactory;

    protected $fillable = [
        'quantidade',
        'valor_uni',
        'valor_pagto',
        'data_pagto',
        'id_cliente',
        'id_colaborador',
        'id_produto',
        'id_venda'
    ];

    public function cliente(): hasOne
    {
        return $this->hasOne(Cliente::class);
    }

    public function colaborador(): hasOne
    {
        return $this->hasOne(Colaborador::class);
    }
    
    public function produto(): hasOne
    {
        return $this->hasOne(produto::class);
    }

    public function venda(): hasOne
    {
        return $this->hasOne(Venda::class);
    }
}
