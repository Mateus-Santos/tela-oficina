<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class NotasItem extends Model
{
    use HasFactory;

    protected $table = 'notas_itens';

    protected $fillable = [
        'nota_id',
        'itemable_type',
        'itemable_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'desconto',
        'valor_total',
        'garantia_dias',
        'garantia_inicio',
        'garantia_fim',
    ];


    public function itemable()
    {
        return $this->morphTo();
    }

    public function nota()
    {
        return $this->belongsTo(Nota::class, 'nota_id');
    }

    public function getTipoFormatadoAttribute(): string
    {
        return match ($this->itemable_type) {
            \App\Models\OrdemServico::class, 'App\Models\OrdemServico' => 'Serviço',
            \App\Models\Produto::class, 'App\Models\Produto'           => 'Produto',
            default                                                     => 'Outro',
        };
    }
}