#!/usr/bin/env python3
"""FEUX L03N: alterações PHP ancoradas no HEAD local L02C exato."""
from pathlib import Path
import sys
root=Path(sys.argv[1]).resolve()

def patch(path, old, new):
 p=root/path
 before=p.read_text()
 count=before.count(old)
 assert count==1, f'ANCORA_DIVERGENTE={path} COUNT={count} TEXT={old[:100]!r}'
 p.write_text(before.replace(old,new,1))
 print('PATCH_L03N='+path)

patch('app/Actions/Notas/AlterarEtapaNota.php',
'''        ?int $userId = null,
        ?string $motivo = null
    ): Nota {''',
'''        ?int $userId = null,
        ?string $motivo = null,
        bool $finalizando = false
    ): Nota {''')
patch('app/Actions/Notas/AlterarEtapaNota.php',
'''                $userId,
                $motivo
            ) {''',
'''                $userId,
                $motivo,
                $finalizando
            ) {''')
patch('app/Actions/Notas/AlterarEtapaNota.php',
'''                /*
                 * =====================================================
                 * MESMA ETAPA''',
'''                /* L03N: etapa final só pode ser alcançada pela Action
                 * FinalizarNota, que valida estoque, O.S., parcelas e conta. */
                if ($etapa->tipo === 'final' && ! $finalizando) {
                    throw new InvalidArgumentException(
                        'A última etapa exige finalização real. Utilize Finalizar Nota na Central.'
                    );
                }

                /*
                 * =====================================================
                 * MESMA ETAPA''')
patch('app/Actions/Notas/FinalizarNota.php',
'''                'Movimentação automática pela finalização da Nota.'
            );''',
'''                'Movimentação automática pela finalização da Nota.',
                true // único acesso autorizado à etapa final
            );''')
patch('app/Models/Nota.php',
'''    public function itens(): HasMany
    {''',
'''    /**
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
                throw new \\Illuminate\\Validation\\ValidationException(
                    validator([], [])->after(static function ($validator) {
                        $validator->errors()->add('etapa_id',
                            'Nota finalizada deve permanecer na última etapa operacional.');
                    })
                );
            }
        });
    }

    public function itens(): HasMany
    {''')
# Note: Laravel ValidationException instance should be created through withMessages, simpler and certain.
p=root/'app/Models/Nota.php'
s=p.read_text(); s=s.replace('''throw new \\Illuminate\\Validation\\ValidationException(
                    validator([], [])->after(static function ($validator) {
                        $validator->errors()->add('etapa_id',
                            'Nota finalizada deve permanecer na última etapa operacional.');
                    })
                );''','''throw \\Illuminate\\Validation\\ValidationException::withMessages([
                    'etapa_id' => 'Nota finalizada deve permanecer na última etapa operacional.',
                ]);''')
p.write_text(s)
patch('routes/web.php',
'''        Route::get('/notas/painel/api/opcoes', [\\App\\Http\\Controllers\\NotasVueController::class, 'opcoes'])''',
'''        Route::get('/notas/painel/api/etapas/lote', [\\App\\Http\\Controllers\\NotasVueController::class, 'previsualizarLote'])
            ->name('notas.vue.etapas.lote.preview');
        Route::post('/notas/painel/api/etapas/lote', [\\App\\Http\\Controllers\\NotasVueController::class, 'moverLote'])
            ->name('notas.vue.etapas.lote.executar');
        Route::get('/notas/painel/api/opcoes', [\\App\\Http\\Controllers\\NotasVueController::class, 'opcoes'])''')
