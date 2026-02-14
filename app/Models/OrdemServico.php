<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdemServico extends Model
{
    use HasFactory;

    public function veiculoCliente()
    {
        return $this->belongsTo(VeiculosClientes::class, 'veiculo_cliente_id');
    }

}
