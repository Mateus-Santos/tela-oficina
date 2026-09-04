<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CompraItem extends Model
{
    use HasFactory;

    protected $table = 'compra_itens';

    protected $fillable = [
        'compra_id',
        'produto_id',
        'descricao',
        'quantidade',
        'quantidade_conferida',
        'valor_unitario',
        'desconto',
        'valor_total',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
        'quantidade_conferida' => 'decimal:3',
        'valor_unitario' => 'decimal:2',
        'desconto' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function movimentacoesEstoque(): MorphMany
    {
        return $this->morphMany(MovimentacaoEstoque::class, 'origem');
    }
}
