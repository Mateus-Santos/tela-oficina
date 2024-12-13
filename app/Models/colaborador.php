<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Colaborador extends Model
{
    use HasFactory;

    protected $table = 'colaborador';

    protected $primaryKey = 'id_colaborador';

    protected $fillable = [
        'id_colaborador',
        'id_pessoa',
        'id_user',
        'chave_pix',
        'conta_banco',
    ];

    public function user(): hasOne
    {
        return $this->hasOne(User::class, 'id', 'id_user');
    } 

    public function pecavenda(): hasMany
    {
        return $this->hasMany(PecaVenda::class, 'id', 'id_user');
    }
}
