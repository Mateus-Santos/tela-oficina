<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\belongsTo;

class Veiculo extends Model
{
    use HasFactory;

    protected $fillable = [
        'placa',
        'ano',
        'marca',
        'cor',
    ];

    public function user(): hasOne
    {
        return $this->hasOne(User::class);
    }

    public function Contratoservico(): belongsTo
    {
        return $this->belongsTo(ContratoServico::class);
    }

}
