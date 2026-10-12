<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etapa extends Model
{
    use HasFactory;

    protected $table = 'etapas';

    protected $fillable = [
        'nome',
        'slug',
        'descricao',
        'cor',
        'ordem',
        'tipo',
        'aplica_nota',
        'aplica_ordem_servico',
        'ativo',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'aplica_nota' => 'boolean',
        'aplica_ordem_servico' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function notas(): HasMany
    {
        return $this->hasMany(
            Nota::class,
            'etapa_id'
        );
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(
            OrdemServico::class,
            'etapa_id'
        );
    }

    public function historicosNotasOrigem(): HasMany
    {
        return $this->hasMany(
            NotaEtapaHistorico::class,
            'etapa_origem_id'
        );
    }

    public function historicosNotasDestino(): HasMany
    {
        return $this->hasMany(
            NotaEtapaHistorico::class,
            'etapa_destino_id'
        );
    }

    public function historicosOrdensServicoOrigem(): HasMany
    {
        return $this->hasMany(
            OrdemServicoEtapaHistorico::class,
            'etapa_origem_id'
        );
    }

    public function historicosOrdensServicoDestino(): HasMany
    {
        return $this->hasMany(
            OrdemServicoEtapaHistorico::class,
            'etapa_destino_id'
        );
    }

    public function scopeAtivas(
        Builder $query
    ): Builder {
        return $query
            ->where(
                'ativo',
                true
            )
            ->orderBy('ordem')
            ->orderBy('id');
    }

    public function scopeParaNota(
        Builder $query
    ): Builder {
        return $query->where(
            'aplica_nota',
            true
        );
    }

    public function scopeParaOrdemServico(
        Builder $query
    ): Builder {
        return $query->where(
            'aplica_ordem_servico',
            true
        );
    }

    public function scopeIniciais(
        Builder $query
    ): Builder {
        return $query->where(
            'tipo',
            'inicial'
        );
    }

    public function scopeFinais(
        Builder $query
    ): Builder {
        return $query->where(
            'tipo',
            'final'
        );
    }

    public static function inicialParaNota(): ?self
    {
        return self::query()
            ->paraNota()
            ->ativas()
            ->iniciais()
            ->first();
    }

    public static function inicialParaOrdemServico(): ?self
    {
        return self::query()
            ->paraOrdemServico()
            ->ativas()
            ->iniciais()
            ->first();
    }
}
