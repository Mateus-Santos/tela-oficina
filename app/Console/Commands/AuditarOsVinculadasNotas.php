<?php

namespace App\Console\Commands;

use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Services\Nota\AuditarOsVinculadas;
use Illuminate\Console\Command;
use Throwable;

class AuditarOsVinculadasNotas extends Command
{
    protected $signature = 'notas:auditar-os
        {--nota= : ID de uma Nota para filtrar a auditoria}
        {--limite=2000 : Maximo de vinculos listados por execucao}
        {--corrigir : Corrigir apenas OS abertas de uma Nota finalizada}
        {--confirmar= : Repetir ID da Nota para autorizar a correcao}';

    protected $description = 'Auditar OS vinculadas a Notas; correcao somente com confirmacao explicita';

    public function handle(AuditarOsVinculadas $auditoria): int
    {
        $nota = $this->option('nota');
        $notaId = filter_var($nota, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($nota !== null && $notaId === false) {
            $this->error('ID de Nota invalido.');
            return self::FAILURE;
        }
        $limite = filter_var($this->option('limite'), FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 10000]]);
        if ($limite === false) {
            $this->error('Limite deve estar entre 1 e 10000.');
            return self::FAILURE;
        }
        if ($this->option('corrigir') && ($notaId === false || $notaId === null
            || (string) $this->option('confirmar') !== (string) $notaId)) {
            $this->error('Para corrigir, informe --nota=ID --corrigir --confirmar=ID.');
            return self::FAILURE;
        }

        try {
            $rows = $auditoria->auditar($notaId === false ? null : $notaId, $limite);
            $this->table(['Item', 'Nota', 'OS', 'Nota status', 'OS status', 'Classificacao'],
                array_map(fn ($r) => array_values($r), $rows));
            $query = NotasItem::query()->where('itemable_type', OrdemServico::class)
                ->when($notaId !== null, fn ($q) => $q->where('nota_id', $notaId));
            $total = $query->count();
            $this->info('Vinculos exibidos: '.count($rows).' de '.$total);
            if ($total > $limite) {
                $this->warn('Resultado truncado; execute por Nota ou aumente --limite.');
            }
            if (!$this->option('corrigir')) {
                $this->info('MODO SOMENTE LEITURA: nenhuma alteracao foi executada.');
                return self::SUCCESS;
            }
            $alteradas = $auditoria->corrigirNota((int) $notaId);
            $this->info("OS finalizadas com historico: {$alteradas}.");
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Operacao bloqueada: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
