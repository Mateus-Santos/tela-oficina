<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    public const PERFIL_ADMIN = 'admin';

    public const PERFIL_CLIENTE = 'cliente';

    public const PERFIL_COLABORADOR = 'colaborador';

    protected $fillable = [
        'pessoa_id',
        'email',
        'password',
        'status',
        'perfil',
        'google_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'boolean',
    ];

    protected $appends = [
        'name',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(
            Pessoa::class,
            'pessoa_id'
        );
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(
            Cliente::class,
            'pessoa_id',
            'pessoa_id'
        );
    }

    public function colaborador(): HasOne
    {
        return $this->hasOne(
            Colaborador::class,
            'id_user'
        );
    }

    public function isAdmin(): bool
    {
        return $this->perfil
            === self::PERFIL_ADMIN;
    }

    public function isCliente(): bool
    {
        return $this->perfil
            === self::PERFIL_CLIENTE;
    }

    public function isColaborador(): bool
    {
        return $this->perfil
            === self::PERFIL_COLABORADOR;
    }

    public function getNameAttribute(): ?string
    {
        return $this->pessoa?->nome;
    }
}
