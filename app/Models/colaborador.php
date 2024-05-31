<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Colaborador extends Model
{
    use HasFactory;

    protected $table = 'colaborador';


    public function pessoa()
    {
        return $this->hasOne(Pessoa::class, 'id_pessoa', 'id_pessoa');
    }

    public function users()
    {
        return $this->hasOne(User::class, 'id', 'id_user');
    }
}
