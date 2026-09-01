<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaReceber extends Model
{
    use HasFactory;

    protected $table = 'contas_receber';

    protected $fillable = [
        'cliente_id',
        'nota_id',
        'categoria_financeira_id',
        'descricao',
        'valor_original',
        'desconto',
        'juros',
        'multa',
        'data_emissao',
        'data_vencimento',
        'data_quitacao',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'valor_original' => 'decimal:2',
        'desconto' => 'decimal:2',
        'juros' => 'decimal:2',
        'multa' => 'decimal:2',
        'data_emissao' => 'date',
        'data_vencimento' => 'date',
        'data_quitacao' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
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

    public function recebimentos(): HasMany
    {
        return $this->hasMany(
            Recebimento::class,
            'conta_receber_id'
        );
    }
}
