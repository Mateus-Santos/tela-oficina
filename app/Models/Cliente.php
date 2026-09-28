<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'pontos',
        'pessoa_id',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function veiculosClientes(): BelongsToMany
    {
        return $this->belongsToMany(
            VeiculosCliente::class,
            'cliente_veiculo_cliente',
            'cliente_id',
            'veiculo_cliente_id'
        )->withTimestamps();
    }
}
