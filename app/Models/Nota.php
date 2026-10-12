<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas';

    protected $fillable = [
        'cliente_id',
        'veiculo_cliente_id',
        'etapa_id',
        'tipo',
        'status',
        'subtotal',
        'desconto',
        'total',
        'observacoes',
        'km',
        'km_proxima_troca_oleo',
    ];

    protected $casts = [
        'etapa_id' => 'integer',
        'km' => 'integer',
        'km_proxima_troca_oleo' => 'integer',
        'subtotal' => 'decimal:2',
        'desconto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(
            NotasItem::class,
            'nota_id'
        );
    }

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
            NotaEtapaHistorico::class,
            'nota_id'
        )->orderByDesc('id');
    }

    public function contaReceber(): HasOne
    {
        return $this->hasOne(
            ContaReceber::class,
            'nota_id'
        );
    }

    public function scopeFiltro(
        Builder $query,
        array $filters
    ): Builder {
        /*
         * =====================================================
         * CLIENTE
         * =====================================================
         */
        if (! empty($filters['cliente'])) {
            $query->whereHas(
                'cliente.pessoa',
                function (Builder $query) use ($filters) {
                    $query->where(
                        'nome',
                        'like',
                        '%'.$filters['cliente'].'%'
                    );
                }
            );
        }

        /*
         * =====================================================
         * VEÍCULO
         * =====================================================
         */
        if (! empty($filters['veiculo'])) {
            $query->whereHas(
                'veiculosCliente.veiculo',
                function (Builder $query) use ($filters) {
                    $query->where(
                        'nome',
                        'like',
                        '%'.$filters['veiculo'].'%'
                    );
                }
            );
        }

        /*
         * =====================================================
         * PLACA
         * =====================================================
         */
        if (! empty($filters['placa'])) {
            $placa = strtoupper(
                trim($filters['placa'])
            );

            $query->whereHas(
                'veiculosCliente',
                function (Builder $query) use ($placa) {
                    $query->where(
                        'placa',
                        'like',
                        '%'.$placa.'%'
                    );
                }
            );
        }

        /*
         * =====================================================
         * TIPO
         * =====================================================
         */
        if (! empty($filters['tipo'])) {
            $query->where(
                'tipo',
                $filters['tipo']
            );
        }

        /*
         * =====================================================
         * ETAPA
         * =====================================================
         */
        if (! empty($filters['etapa_id'])) {
            $query->where(
                'etapa_id',
                $filters['etapa_id']
            );
        }

        /*
         * =====================================================
         * STATUS
         * =====================================================
         *
         * Regra padrão:
         *
         * Sem filtro explícito, a listagem mostra somente
         * Notas que ainda estão em andamento no sistema:
         * status Aberto.
         *
         * Finalizado inclui Concluido por compatibilidade
         * com registros legados.
         */
        $status = $filters['status'] ?? null;

        if ($status === 'Todos') {
            return $query;
        }

        if ($status === 'Finalizado') {
            $query->whereIn(
                'status',
                [
                    'Finalizado',
                    'Concluido',
                ]
            );

            return $query;
        }

        if ($status === 'Cancelado') {
            $query->where(
                'status',
                'Cancelado'
            );

            return $query;
        }

        /*
         * Tanto "Aberto" explícito quanto ausência de filtro
         * caem aqui.
         */
        $query->where(
            'status',
            'Aberto'
        );

        return $query;
    }
}
