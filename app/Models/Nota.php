<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas';

    protected $fillable = [
        'cliente_id',
        'veiculo_cliente_id',
        'tipo',
        'status',
        'subtotal',
        'desconto',
        'total',
        'observacoes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'desconto' => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    public function itens()
    {
        return $this->hasMany(NotasItem::class, 'nota_id');
    }

    public function veiculosCliente()
    {
        return $this->belongsTo(VeiculosCliente::class, 'veiculo_cliente_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function scopeFiltro(Builder $query, array $filters)
    {
        if (!empty($filters['cliente'])) {
            $query->whereHas('cliente', function ($q) use ($filters) {
                $q->where('nome', 'like', '%' . $filters['cliente'] . '%');
            });
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', '!=', 'Cancelado');
        }

        return $query;
    }

    public function notasItem()
    {
        return $this->hasMany(NotasItem::class, 'nota_id');
    }
}
