<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParcelaContaPagar extends Model
{
    use HasFactory;

    protected $table = 'parcelas_contas_pagar';

    protected $fillable = [
        'conta_pagar_id',
        'numero',
        'valor',
        'data_vencimento',
    ];

    protected $casts = [
        'numero' => 'integer',
        'valor' => 'decimal:2',
        'data_vencimento' => 'date',
    ];

    public function contaPagar(): BelongsTo
    {
        return $this->belongsTo(
            ContaPagar::class,
            'conta_pagar_id'
        );
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(
            PagamentoContaPagar::class,
            'parcela_conta_pagar_id'
        );
    }

    public function pagamentosAtivos(): HasMany
    {
        return $this->pagamentos()
            ->whereNull('estornado_em');
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
        return max(
            0,
            round(
                (float) $this->valor - $this->valor_pago,
                2
            )
        );
    }

    public function estaPaga(): bool
    {
        return $this->saldo <= 0;
    }

    public function estaVencida(): bool
    {
        return !$this->estaPaga()
            && $this->data_vencimento->isBefore(today());
    }
}
