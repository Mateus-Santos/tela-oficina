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
        'id_manutencao',
        'id_veiculo',
        'setor',
        'descricao',
        'valor',
        'nivel',
    ];

    public function veiculo(): hasOne
    {
        return $this->hasOne(Veiculo::class, 'id_veiculo', 'id_veiculo');
    }

}
