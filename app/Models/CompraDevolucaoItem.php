<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CompraDevolucaoItem extends Model
{
    protected $table = 'compra_devolucao_itens';

    protected $fillable = [
        'compra_devolucao_id', 'compra_item_id', 'produto_id',
        'quantidade', 'movimentacao_estoque_id',
        'modalidade', 'valor_abatimento',
    ];

    protected $casts = ['quantidade' => 'decimal:3'];

    public function devolucao(): BelongsTo
    {
        return $this->belongsTo(CompraDevolucao::class, 'compra_devolucao_id');
    }

    public function itemCompra(): BelongsTo
    {
        return $this->belongsTo(CompraItem::class, 'compra_item_id');
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
