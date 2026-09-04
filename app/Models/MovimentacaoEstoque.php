<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimentacaoEstoque extends Model
{
    use HasFactory;

    protected $table = 'movimentacao_estoques';

    protected $fillable = [
        'produto_id',
        'tipo',
        'quantidade',
        'saldo_anterior',
        'saldo_posterior',
        'valor_unitario',
        'origem_type',
        'origem_id',
        'usuario_id',
        'observacoes',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
        'saldo_anterior' => 'decimal:3',
        'saldo_posterior' => 'decimal:3',
        'valor_unitario' => 'decimal:2',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function origem(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
