<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function cliente(): hasMany
    {
        return $this->hasMany(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function colaborador(): hasMany
    {
        return $this->hasMany(Colaborador::class, 'id_colaborador', 'id_colaborador');
    }
    
    public function peca(): hasMany
    {
        return $this->hasMany(hasMany::class, 'id_peca', 'id_peca');
    }

    public function venda(): hasMany
    {
        return $this->hasMany(hasMany::class, 'id', 'id_venda');
    }
}
