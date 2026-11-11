<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingCadastroPendente extends Model
{
    protected $table = 'marketing_cadastros_pendentes';

    protected $fillable = [
        'nome', 'email', 'whatsapp', 'senha_hash', 'token_hash',
        'expira_em', 'consentido_em',
    ];

    protected $hidden = ['senha_hash', 'token_hash'];

    protected function casts(): array
    {
        return [
            'expira_em' => 'datetime',
            'consentido_em' => 'datetime',
        ];
    }
}
