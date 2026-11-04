<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParcelaContaPagarSocio extends Model
{
    protected $table = 'parcelas_contas_pagar_socios';

    protected $fillable = ['conta_pagar_socio_id', 'numero', 'valor', 'data_vencimento'];

    protected $casts = [
        'valor' => 'decimal:2',
        'numero' => 'integer',
        'data_vencimento' => 'date',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaPagarSocio::class, 'conta_pagar_socio_id');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoContaPagarSocio::class, 'parcela_conta_pagar_socio_id');
    }

    public function pagamentosAtivos(): HasMany
    {
        return $this->pagamentos()->whereNull('estornado_em');
    }

    public function getValorPagoAttribute(): float
    {
        if ($this->relationLoaded('pagamentosAtivos')) {
            return (float) $this->pagamentosAtivos->sum('valor');
        }

        return (float) $this->pagamentosAtivos()->sum('valor');
    }

    public function getSaldoAttribute(): float
    {
        return max(0, round((float) $this->valor - $this->valor_pago, 2));
    }
}
