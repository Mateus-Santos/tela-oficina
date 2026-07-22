<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeiculosCliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'ano',
        'placa',
        'cor',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->BelongsTo(Veiculo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->BelongsTo(Cliente::class);
    }

    public function ordensServico()
    {
        return $this->hasMany(OrdemServico::class, 'veiculo_cliente_id');
    }

}