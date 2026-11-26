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

    /**
     * Uma Nota finalizada não pode ser gravada fora da última etapa.
     * Também bloqueia mudança manual da etapa de Nota já finalizada.
     * Atualizações SQL diretas não acionam eventos Eloquent: auditoria separada.
     */
    protected static function booted(): void
    {
        static::saving(static function (self $nota): void {
            if (! in_array($nota->status, ['Finalizado', 'Concluido'], true)) return;
            if (! $nota->isDirty('status') && ! $nota->isDirty('etapa_id')) return;
            $valida = Etapa::query()->paraNota()->ativas()->finais()
                ->whereKey($nota->etapa_id)->exists();
            if (! $valida) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'etapa_id' => 'Nota finalizada deve permanecer na última etapa operacional.',
                ]);
            }
        });
    }

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

    public function anexosVinculos(): HasMany
    {
        return $this->hasMany(AnexoVinculo::class, 'vinculavel_id')
            ->where('vinculavel_type', self::class);
    }

    public function scopeFiltro(
        Builder $query,
        array $filters
    ): Builder {
        // Busca exata pelo codigo da Nota (inclui status historico).
        if (!empty($filters['id'])) {
            $query->whereKey((int) $filters['id']);
        }

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
