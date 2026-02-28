<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServico extends Model
{
    use HasFactory;

    public function veiculosCliente()
    {
        return $this->belongsTo(VeiculosCliente::class, 'veiculo_cliente_id');
    }

    public function setorServico()
    {
        return $this->belongsTo(SetorServico::class, 'setor_servico_id');
    }
}