<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParcelaContaReceber extends Model
{
    use HasFactory;

    protected $table = 'parcelas_contas_receber';

    protected $fillable = [
        'conta_receber_id',
        'numero',
        'valor',
        'data_vencimento',
    ];

    protected $casts = [
        'numero' => 'integer',
        'valor' => 'decimal:2',
        'data_vencimento' => 'date',
    ];

    public function contaReceber(): BelongsTo
    {
        return $this->belongsTo(
            ContaReceber::class,
            'conta_receber_id'
        );
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(
            Recebimento::class,
            'parcela_conta_receber_id'
        );
    }

    public function recebimentosAtivos(): HasMany
    {
        return $this->recebimentos()
            ->whereNull('estornado_em');
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
        return max(
            0,
            round(
                (float) $this->valor - $this->valor_recebido,
                2
            )
        );
    }

    public function estaQuitada(): bool
    {
        return $this->saldo <= 0;
    }

    public function estaVencida(): bool
    {
        return !$this->estaQuitada()
            && $this->data_vencimento->isBefore(today());
    }
}
