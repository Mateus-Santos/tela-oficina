<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParcelaContaReceberSocio extends Model
{
    protected $table = 'parcelas_contas_receber_socios';

    protected $fillable = ['conta_receber_socio_id', 'numero', 'valor', 'data_vencimento'];

    protected $casts = [
        'valor' => 'decimal:2',
        'numero' => 'integer',
        'data_vencimento' => 'date',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaReceberSocio::class, 'conta_receber_socio_id');
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(RecebimentoContaReceberSocio::class, 'parcela_conta_receber_socio_id');
    }

    public function recebimentosAtivos(): HasMany
    {
        return $this->recebimentos()->whereNull('estornado_em');
    }

    public function getValorRecebidoAttribute(): float
    {
        if ($this->relationLoaded('recebimentosAtivos')) {
            return (float) $this->recebimentosAtivos->sum('valor');
        }
        return (float) $this->recebimentosAtivos()->sum('valor');
    }

    public function getSaldoAttribute(): float
    {
        return max(0, round((float) $this->valor - $this->valor_recebido, 2));
    }
}
