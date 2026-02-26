<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdemServicoItem extends Model
{
    use HasFactory;

    public function OrdemServico()
    {
        return $this->hasMany(OrdemServico::class);
    }
}
