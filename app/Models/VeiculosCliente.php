<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VeiculosCliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'ano',
        'placa',
        'cor',
        'veiculo_id',

        // TEMPORÁRIO.
        'cliente_id',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    /*
     * Compatibilidade temporária.
     * Será removido junto com cliente_id.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(
            Cliente::class,
            'cliente_veiculo_cliente',
            'veiculo_cliente_id',
            'cliente_id'
        )->withTimestamps();
    }

    public function ordensServico()
    {
        return $this->hasMany(
            OrdemServico::class,
            'veiculo_cliente_id'
        );
    }
}
