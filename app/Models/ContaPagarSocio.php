<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaPagarSocio extends Model
{
    protected $table = 'contas_pagar_socios';

    protected $fillable = [
        'socio_id', 'descricao', 'credor', 'categoria', 'valor',
        'data_emissao', 'status', 'observacoes',
    ];

    protected $casts = ['valor' => 'decimal:2', 'data_emissao' => 'date'];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(ParcelaContaPagarSocio::class)->orderBy('numero');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoContaPagarSocio::class);
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
