<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdemServicoItem extends Model
{
    use HasFactory;

    protected $table = 'ordem_servico_itens';

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class);
    }

}
