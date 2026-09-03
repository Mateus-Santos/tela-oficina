<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Compra extends Model
{
    use HasFactory;

    protected $table = 'compras';

    protected $fillable = [
        'fornecedor_id',
        'numero_nf',
        'serie_nf',
        'chave_nf',
        'data_emissao',
        'data_entrada',
        'valor_produtos',
        'desconto',
        'frete',
        'outras_despesas',
        'valor_total',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'data_entrada' => 'date',
        'valor_produtos' => 'decimal:2',
        'desconto' => 'decimal:2',
        'frete' => 'decimal:2',
        'outras_despesas' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(CompraItem::class);
    }

    public function anexos(): MorphMany
    {
        return $this->morphMany(Anexo::class, 'anexavel');
    }
}
