<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pessoa extends Model
{
    use HasFactory;

    protected $table = 'pessoa';

    protected $foreignKey = 'id_endereco';

    public function endereco()
    {
        return $this->hasMany(Endereco::class, 'id_endereco');
    }

}
