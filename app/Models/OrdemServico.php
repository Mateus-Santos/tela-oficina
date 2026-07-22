<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServico extends Model
{
    use HasFactory;

    protected $fillable = [
        'setor_servico_id',
        'veiculo_cliente_id',
        'status',
        'descricao',
        'valor_total',
        'data_abertura',
        'data_fechamento',
        ''
    ];

    public function veiculosCliente()
    {
        return $this->belongsTo(VeiculosCliente::class, 'veiculo_cliente_id');
    }

    public function setorServico()
    {
        return $this->belongsTo(SetorServico::class, 'setor_servico_id');
    }
}