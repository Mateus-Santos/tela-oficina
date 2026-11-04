<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaReceberSocio extends Model
{
    protected $table = 'contas_receber_socios';

    protected $fillable = [
        'socio_id', 'devedor', 'descricao', 'origem', 'identificacao_cartao',
        'valor', 'data_operacao', 'status', 'data_quitacao', 'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_operacao' => 'date',
        'data_quitacao' => 'date',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(ParcelaContaReceberSocio::class)->orderBy('numero');
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(RecebimentoContaReceberSocio::class);
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
