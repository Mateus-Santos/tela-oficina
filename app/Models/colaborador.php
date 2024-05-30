<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Colaborador extends Model
{
    use HasFactory;

    protected $table = 'colaborador';

    protected $fillable = [
        'id_colaborador',
        'id_pessoa',
        'id_user',
        'chave_pix',
        'conta_banco',
    ];

    public function pessoa(): HasOne
    {
        return $this->hasOne(Pessoa::class);
    }

    public function users(): HasOne
    {
        return $this->hasOne(Users::class);
    }
}
