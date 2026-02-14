<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Servico extends Model
{
    use HasFactory;

    public function ordemservico(): hasOne
    {
        return $this->hasOne(ordemservico::class);
    }

}
