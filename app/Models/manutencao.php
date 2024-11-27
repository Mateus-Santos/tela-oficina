<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Manutencao extends Model
{
    use HasFactory;

    protected $table = 'manutencao';

    protected $fillable = [
        'id_contrato_servico',
        'setor',
        'descricao',
        'valor',
        'nivel',
    ];

    public function contratoservico(): hasOne
    {
        return $this->hasOne(ContratoServico::class, 'id', 'id_contrato_servico');
    }

}
