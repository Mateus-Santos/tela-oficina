<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;



class PecaVenda extends Model
{

    use HasFactory;

    protected $table = 'peca_venda';

    protected $fillable = [
        'quantidade',
        'valor_uni',
        'valor_pagto',
        'data_pagto',
        'id_cliente',
        'id_colaborador',
        'id_peca',
        'id_venda'
    ];

    public function cliente(): hasOne
    {
        return $this->hasOne(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function colaborador(): hasOne
    {
        return $this->hasOne(Colaborador::class, 'id_colaborador', 'id_colaborador');
    }
    
    public function peca(): hasOne
    {
        return $this->hasOne(Peca::class, 'id_peca', 'id_peca');
    }

    public function venda(): hasOne
    {
        return $this->hasOne(Venda::class, 'id', 'id_venda');
    }
}
