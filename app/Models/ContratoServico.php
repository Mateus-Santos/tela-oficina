<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\belongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContratoServico extends Model
{
    use HasFactory;

    protected $table = 'contrato_servico';

    protected $fillable = [
        'data_fechamento',
        'descricao',
        'data_abertura',
        'status',
        'id_veiculo',
    ];

    public function manutencao(): belongsTo
    {
        return $this->belongsTo(Manutencao::class, 'id', 'id_contrato_servico');
    }

    public function veiculo(): hasOne
    {
        return $this->hasOne(Veiculo::class, 'id_veiculo', 'id_veiculo');
    }
}
