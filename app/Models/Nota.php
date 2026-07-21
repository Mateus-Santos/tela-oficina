<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function veiculoscliente()
    {
        return $this->belongsTo(veiculoscliente::class, 'veiculo_cliente_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}