<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Endereco extends Model
{
    use HasFactory;

    protected $table = 'endereco';

    protected $foreignKey = 'id_pessoa';

    public function pessoa()
    {
        return $this->hasOne(Pessoa::class, 'id_pessoa');
    }

}
