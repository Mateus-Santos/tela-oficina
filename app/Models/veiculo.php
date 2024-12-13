<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\belongsTo;

class Veiculo extends Model
{
    use HasFactory;

    protected $table = 'veiculo';

    protected $fillable = [
        'id_veiculo',
        'id_user',
        'placa',
        'ano',
        'marca',
        'cor',
    ];

    public function user(): hasOne
    {
        return $this->hasOne(User::class, 'id', 'id_user');
    }

    public function Contratoservico(): belongsTo
    {
        return $this->belongsTo(ContratoServico::class, 'id_veiculo', 'id_veiculo');
    }

}
