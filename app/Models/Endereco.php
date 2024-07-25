<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Endereco extends Model
{
    use HasFactory;

    protected $table = 'endereco';

    protected $foreignKey = 'id_pessoa';

    protected $fillable = [
        'cep',
        'cidade',
        'bairro',
        'estado',
        'rua',
        'endereco',
        'numero',
        'ponto_referencia',
    ];

    public function pessoa(): hasOne
    {
        return $this->hasOne(Pessoa::class, 'id_pessoa', 'id_pessoa');
    }
}
