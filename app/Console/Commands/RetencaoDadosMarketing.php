<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Retencao explicita de dados temporarios, sem apagar solicitacoes ou notas. */
final class RetencaoDadosMarketing extends Command
{
    protected $signature = 'marketing:retencao
        {--executar : Aplicar a limpeza; sem a opcao, somente apresenta contagens}';

    protected $description = 'Audita ou limpa IP cifrado antigo e cadastros de Marketing expirados';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql'
            || !Schema::hasTable('marketing_leads')
            || !Schema::hasColumn('marketing_leads', 'ip_criptografado')
            || !Schema::hasTable('marketing_cadastros_pendentes')) {
            $this->error('Esquema do Marketing indisponivel; nenhuma alteracao feita.');

            return self::FAILURE;
        }

        // Limites defensivos impedem exclusao ampla por configuracao acidental.
        $diasIp = max(1, min(365, (int) config('marketing.retencao_ip_dias', 30)));
        $diasPendentes = max(1, min(30, (int) config('marketing.retencao_pendentes_dias', 7)));
        $limiteIp = now()->subDays($diasIp);
        $limitePendentes = now()->subDays($diasPendentes);

        $consultaIp = static fn () => DB::table('marketing_leads')
            ->whereNotNull('ip_criptografado')
            ->where('created_at', '<', $limiteIp);
        $consultaPendentes = static fn () => DB::table('marketing_cadastros_pendentes')
            ->where('expira_em', '<', $limitePendentes);

        $this->line('IPs cifrados elegiveis: '.$consultaIp()->count());
        $this->line('Cadastros expirados elegiveis: '.$consultaPendentes()->count());

        if (!$this->option('executar')) {
            $this->info('SIMULACAO: nenhum dado alterado. Use --executar apos backup.');

            return self::SUCCESS;
        }

        [$ips, $pendentes] = DB::transaction(static function () use ($consultaIp, $consultaPendentes): array {
            return [
                $consultaIp()->update(['ip_criptografado' => null]),
                $consultaPendentes()->delete(),
            ];
        });

        $this->info("Limpeza aplicada: {$ips} IPs descaracterizados; {$pendentes} cadastros expirados removidos.");

        return self::SUCCESS;
    }
}
