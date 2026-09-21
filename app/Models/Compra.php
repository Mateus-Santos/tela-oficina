<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    use HasFactory;

    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_CONFERINDO = 'conferindo';
    public const STATUS_APROVADA = 'aprovada';
    public const STATUS_CANCELADA = 'cancelada';

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

    public function anexosVinculos(): HasMany
    {
        return $this->hasMany(AnexoVinculo::class, 'vinculavel_id')
            ->where('vinculavel_type', self::class);
    }

    public function estaPendente(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    public function estaEmConferencia(): bool
    {
        return $this->status === self::STATUS_CONFERINDO;
    }

    public function estaAprovada(): bool
    {
        return $this->status === self::STATUS_APROVADA;
    }

    public function estaCancelada(): bool
    {
        return $this->status === self::STATUS_CANCELADA;
    }

    public function podeSerEditada(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_PENDENTE,
                self::STATUS_CONFERINDO,
            ],
            true
        );
    }

    public function podeIniciarConferencia(): bool
    {
        return $this->estaPendente();
    }

    public function podeSerAprovada(): bool
    {
        return $this->estaEmConferencia();
    }
}
