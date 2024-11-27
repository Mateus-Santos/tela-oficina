<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsTo;

class contrato_manutencao extends Model
{
    use HasFactory;

    protected $table = 'contrato_servico';

    protected $fillable = [
        'data_fechamento',
        'descricao',
        'data_abertura',
        'status',
        'nivel',
    ];

    public function manutencao(): belongsTo
    {
        return $this->belongsTo(Manutencao::class, 'id', 'id_contrato_servico');
    }
}
