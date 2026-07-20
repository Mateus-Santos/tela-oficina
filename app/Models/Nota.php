<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas';

    // Mapeia exatamente as colunas da sua Migration
    protected $fillable = [
        'cliente_id',
        'veiculo_id',
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

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}