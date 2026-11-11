<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'pontos',
        'pessoa_id',
    ];

    public function marketingAcesso(): HasOne
    {
        return $this->hasOne(MarketingClienteAcesso::class, 'cliente_id');
    }

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
