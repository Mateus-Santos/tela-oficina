<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetorServico extends Model
{
    use HasFactory;

    protected $fillable = [
        'setor',
        'nivel'
    ];

    public function ordemServicos()
    {
        return $this->hasMany(OrdemServico::class);
    }

}
