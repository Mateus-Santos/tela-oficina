<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    use HasFactory;

    protected $fillable = [
        'setor_servico_id',
        'veiculo_cliente_id',
        'etapa_id',
        'status',
        'descricao',
        'valor',
        'cliente_id',
        'data_abertura',
        'data_fechamento',
    ];

    protected $casts = [
        'etapa_id' => 'integer',
        'valor' => 'decimal:2',
        'data_abertura' => 'datetime',
        'data_fechamento' => 'datetime',
    ];

    public function veiculosCliente(): BelongsTo
    {
        return $this->belongsTo(
            VeiculosCliente::class,
            'veiculo_cliente_id'
        );
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    public function setorServico(): BelongsTo
    {
        return $this->belongsTo(
            SetorServico::class,
            'setor_servico_id'
        );
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(
            Etapa::class,
            'etapa_id'
        );
    }

    public function historicoEtapas(): HasMany
    {
        return $this->hasMany(
            OrdemServicoEtapaHistorico::class,
            'ordem_servico_id'
        )->orderByDesc('id');
    }
}
