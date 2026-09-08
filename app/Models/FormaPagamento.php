<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormaPagamento extends Model
{
    use HasFactory;

    protected $table = 'formas_pagamento';

    protected $fillable = [
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function recebimentos(): HasMany
    {
        return $this->hasMany(Recebimento::class);
    }

    public function pagamentosContasPagar(): HasMany
    {
        return $this->hasMany(
            PagamentoContaPagar::class,
            'forma_pagamento_id'
        );
    }

    public function contasPagar(): HasMany
    {
        return $this->hasMany(
            ContaPagar::class,
            'forma_pagamento_id'
        );
    }
}
