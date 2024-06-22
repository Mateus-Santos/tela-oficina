<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PecaVenda extends Model
{
    protected $table = 'peca_venda';

    protected $fillable = [
        'quantidade'
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
        return $this->hasOne(Venda::class, 'id_venda', 'id_venda');
    }
}
