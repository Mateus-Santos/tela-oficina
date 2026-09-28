<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Cliente;

class OrdemServico extends Model
{
    use HasFactory;

    protected $fillable = [
        'setor_servico_id',
        'veiculo_cliente_id',
        'status',
        'descricao',
        'valor',
        'cliente_id',
        'data_abertura',
        'data_fechamento'
    ];

    public function veiculosCliente()
    {
        return $this->belongsTo(
            VeiculosCliente::class,
            'veiculo_cliente_id'
        );
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    public function setorServico(): BelongsTo
    {
        return $this->belongsTo(SetorServico::class, 'setor_servico_id');
    }
}
