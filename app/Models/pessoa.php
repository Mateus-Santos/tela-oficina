<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pessoa extends Model
{
    use HasFactory;

    protected $table = 'pessoa';

    protected $primaryKey = 'id_pessoa';

    protected $fillable = [
        'id_pessoa',
        'nome',
        'data_nascimento',
        'email',
        'cpf',
        'rg',
        'telefone_1',
        'telefone_2',
    ];

    public function endereco(): hasMany
    {
        return $this->hasMany(Endereco::class, 'id_endereco', 'id_endereco');
    }

    public function colaborador(): belongsTo
    {
        return $this->belongsTo(Colaborador::class, 'id_pessoa', 'id_pessoa');
    }

    public function cliente(): belongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente' ,'id_cliente');
    }

}
