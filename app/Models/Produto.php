<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
        'quantidade',
        'estoque_minimo',
        'preco_uni',
        'img',
        'codigo_fabricante',
        'codigo_barras',
        'status',
        'fornecedor_id',
        'marca',
        'ncm',
        'cest',
        'ex_tipi',
        'origem_mercadoria',
        'unidade_comercial',
        'unidade_tributavel',
        'fator_conversao',
        'peso_liquido',
        'peso_bruto',
    ];

    protected $casts = [
        'origem_mercadoria' => 'integer',
        'fator_conversao' => 'decimal:6',
        'peso_liquido' => 'decimal:3',
        'peso_bruto' => 'decimal:3',
    ];

    public function scopeFiltro($query, array $filtros)
    {
        return $query
            ->when($filtros['nome'] ?? null, fn ($q, $v) =>
                $q->where('nome', 'like', "%{$v}%")
            )
            ->when($filtros['codigo_barras'] ?? null, fn ($q, $v) =>
                $q->where('codigo_barras', $v)
            )
            ->when($filtros['codigo_fabricante'] ?? null, fn ($q, $v) =>
                $q->where('codigo_fabricante', $v)
            )
            ->when($filtros['marca'] ?? null, fn ($q, $v) =>
                $q->where('marca', 'like', "%{$v}%")
            )
            ->when($filtros['fornecedor_id'] ?? null, fn ($q, $v) =>
                $q->where('fornecedor_id', $v)
            )
            ->when(
                isset($filtros['status']) && $filtros['status'] !== '',
                fn ($q) =>
                    $q->where('status', (bool) $filtros['status'])
            )
            ->when($filtros['estoque'] ?? null, function ($q, $v) {
                return match ($v) {
                    'com_estoque' => $q->where('quantidade', '>', 0),
                    'sem_estoque' => $q->where('quantidade', 0),
                    'estoque_baixo' => $q->whereColumn(
                        'quantidade',
                        '<=',
                        'estoque_minimo'
                    ),
                    default => $q,
                };
            });
    }

    public function veiculos(): BelongsToMany
    {
        return $this->belongsToMany(Veiculo::class, 'produtos_veiculos');
    }

    public function anexosVinculos(): HasMany
    {
        return $this->hasMany(AnexoVinculo::class, 'vinculavel_id')
            ->where('vinculavel_type', self::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function movimentacoesEstoque(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class);
    }
}
