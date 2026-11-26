<?php

namespace App\Console\Commands;

use App\Actions\Notas\CriarNota;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Regularização pontual de O.S. legada no CLONE FEUX.
 * Não associa a Nota finalizada apenas por igualdade de cliente e veículo.
 * O comando é inoperante em outros bancos por design.
 */
final class RegularizarOsOrfaNaPreview extends Command
{
    protected $signature = 'notas:regularizar-os-orfa
        {--os= : ID da O.S. (somente 3 nesta operação)}
        {--cliente= : ID do cliente esperado}
        {--veiculo= : ID do veículo do cliente esperado}
        {--valor= : Valor bruto esperado, em reais (ex.: 500.00)}
        {--executar : Efetua a transação; por padrão apenas verifica}
        {--confirmar= : Repita o ID para confirmar escrita}';

    protected $description = 'Vincula O.S. #3 órfã a Nota NOVA apenas no banco clonado FEUX, com pré-condições rígidas.';

    public function handle(CriarNota $criarNota): int
    {
        $conexao = DB::connection();
        $database = $conexao->getDatabaseName();
        if (!is_string($database) || !preg_match('/^feux_dados_dev_[0-9]{8}_[0-9]{6}_[0-9]+$/', $database)) {
            $this->error('BLOQUEADO: o banco de dados NÃO é o clone FEUX esperado.');
            return self::FAILURE;
        }
        if (config('database.default') !== 'mysql') {
            $this->error('BLOQUEADO: conexão não é MySQL.');
            return self::FAILURE;
        }

        $osId = filter_var($this->option('os'), FILTER_VALIDATE_INT);
        $clienteId = filter_var($this->option('cliente'), FILTER_VALIDATE_INT);
        $veiculoId = filter_var($this->option('veiculo'), FILTER_VALIDATE_INT);
        $valorEsperado = (string) $this->option('valor');

        // Escopo da correção: apenas a ocorrência verificada pelo usuário.
        if ($osId !== 3 || $clienteId !== 6 || $veiculoId !== 6 || $valorEsperado !== '500.00') {
            $this->error('BLOQUEADO: informe --os=3 --cliente=6 --veiculo=6 --valor=500.00.');
            return self::FAILURE;
        }

        if ($this->option('executar') && (string) $this->option('confirmar') !== '3') {
            $this->error('BLOQUEADO: para executar, acrescente --confirmar=3.');
            return self::FAILURE;
        }

        try {
            if (!$this->option('executar')) {
                $os = OrdemServico::query()->findOrFail(3);
                $this->validarOs($os);
                $this->info('PREVIA_CLONADA=SIM');
                $this->line('OS_ID=3 CLIENTE_ID=6 VEICULO_ID=6 VALOR=500.00 STATUS=aberta');
                $this->line('PLANO=CRIAR_NOTA_ABERTA_NOVA_COM_ITEM_OS_3');
                $this->line('NOTA_59_FINALIZADA=NAO_ALTERADA_NEM_ASSOCIADA');
                $this->line('SIMULACAO_SOMENTE_LEITURA=SIM');
                return self::SUCCESS;
            }

            $notaCriada = DB::transaction(function () use ($criarNota): Nota {
                $os = OrdemServico::query()->lockForUpdate()->findOrFail(3);
                $this->validarOs($os);

                // CriarNota registra o histórico inicial e calcula o valor usando a
                // regra oficial; a criação ocorre dentro da mesma transação.
                $nota = $criarNota->execute([
                    'cliente_id' => 6,
                    'veiculo_cliente_id' => 6,
                    'itens' => [[
                        'itemable_type' => OrdemServico::class,
                        'itemable_id' => 3,
                        'descricao' => trim((string) $os->descricao) ?: 'Serviço da O.S. #3',
                        'quantidade' => 1,
                        'valor_unitario' => '500.00',
                        'desconto' => '0.00',
                    ]],
                ]);

                if ($nota->status !== 'Aberto'
                    || (int) $nota->cliente_id !== 6
                    || (int) $nota->veiculo_cliente_id !== 6
                    || (int) round((float) $nota->total * 100) !== 50000
                    || $nota->itens()->where('itemable_type', OrdemServico::class)
                        ->where('itemable_id', 3)->count() !== 1) {
                    throw new \RuntimeException('Falhou a conferência pós-criação; transação revertida.');
                }
                return $nota;
            }, 3);

            $this->info('REGULARIZACAO=CONCLUIDA');
            $this->info('NOTA_CRIADA_ID='.$notaCriada->id);
            $this->line('OS_3=VINCULADA_UMA_UNICA_VEZ');
            $this->line('STATUS_NOTA=ABERTO VALOR_NOTA=500.00');
            $this->line('NOTA_59=INTACTA');
            $this->line('SEM_BAIXA_ESTOQUE_OU_GERACAO_FINANCEIRA=SIM');
            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                $primeiroErro = collect($e->errors())->flatten()->first();
                $this->error('BLOQUEADO: '.($primeiroErro ?: 'regra de validação recusou a operação.'));
            } else {
                $this->error('BLOQUEADO: '.$e->getMessage());
            }
            $this->error('NENHUMA_CRIACAO_CONFIRMADA=SIM');
            return self::FAILURE;
        }
    }

    private function validarOs(OrdemServico $os): void
    {
        if ($os->status !== 'aberta' || $os->data_fechamento !== null) {
            throw new \DomainException('O.S. #3 não está aberta e sem fechamento.');
        }
        if ((int) $os->cliente_id !== 6 || (int) $os->veiculo_cliente_id !== 6) {
            throw new \DomainException('Cliente ou veículo da O.S. #3 mudou desde a auditoria.');
        }
        if ((int) round((float) $os->valor * 100) !== 50000) {
            throw new \DomainException('Valor da O.S. #3 mudou desde a auditoria.');
        }
        $vinculos = NotasItem::query()
            ->where('itemable_type', OrdemServico::class)
            ->where('itemable_id', $os->id)
            ->count();
        if ($vinculos !== 0) {
            throw new \DomainException('O.S. #3 já está vinculada; nenhuma Nota duplicada será criada.');
        }
    }
}
