<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ContaPagar extends Model
{
    use HasFactory;

    protected $table = 'contas_pagar';

    protected $fillable = [
        'fornecedor_id',
        'nota_id',
        'categoria_financeira_id',
        'forma_pagamento_id',
        'descricao',
        'valor',
        'data_emissao',
        'data_vencimento',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_emissao' => 'date',
        'data_vencimento' => 'date',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(Nota::class);
    }

    public function categoriaFinanceira(): BelongsTo
    {
        return $this->belongsTo(
            CategoriaFinanceira::class,
            'categoria_financeira_id'
        );
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(
            FormaPagamento::class,
            'forma_pagamento_id'
        );
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoContaPagar::class);
    }

    public function pagamentosAtivos(): HasMany
    {
        return $this->pagamentos()->whereNull('estornado_em');
    }

    public function anexos(): MorphMany
    {
        return $this->morphMany(Anexo::class, 'anexavel');
    }

    public function getValorPagoAttribute(): float
    {
        return (float) $this->pagamentosAtivos()->sum('valor');
    }

    public function getSaldoAttribute(): float
    {
        return max(0, round((float) $this->valor - $this->valor_pago, 2));
    }

    public function estaPaga(): bool
    {
        return $this->saldo <= 0;
    }

    public function estaVencida(): bool
    {
        return !$this->estaPaga()
            && !$this->estaCancelada()
            && $this->data_vencimento->isPast();
    }

    public function estaCancelada(): bool
    {
        return $this->status === 'cancelada';
    }
}
