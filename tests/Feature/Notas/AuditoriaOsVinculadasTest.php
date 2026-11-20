<?php

namespace Tests\Feature\Notas;

use App\Models\Cliente;
use App\Models\Etapa;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Models\Pessoa;
use App\Models\SetorServico;
use App\Services\Nota\AuditarOsVinculadas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class AuditoriaOsVinculadasTest extends TestCase
{
    use RefreshDatabase;

    private function cenario(string $statusNota = 'Finalizado', string $statusOs = 'aberta'): array
    {
        $cliente = Cliente::create([
            'pessoa_id' => Pessoa::create(['nome' => 'Cliente OS Legado'])->id,
            'pontos' => 0,
        ]);
        $agora = now();
        $montadora = DB::table('montadoras')->insertGetId([
            'nome' => 'Montadora Auditoria', 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $veiculo = DB::table('veiculos')->insertGetId([
            'montadora_id' => $montadora, 'nome' => 'Veiculo Auditoria',
            'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $veiculoCliente = DB::table('veiculos_clientes')->insertGetId([
            'cliente_id' => $cliente->id, 'veiculo_id' => $veiculo,
            'placa' => 'ABC1D23', 'ano' => 2024, 'cor' => 'Cinza',
            'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $setor = SetorServico::create(['setor' => 'Auditoria', 'nivel' => 1]);
        $inicial = Etapa::query()->where('tipo', 'inicial')->where('aplica_ordem_servico', true)->firstOrFail();
        $os = OrdemServico::create([
            'data_abertura' => $agora, 'setor_servico_id' => $setor->id,
            'veiculo_cliente_id' => $veiculoCliente, 'cliente_id' => $cliente->id,
            'etapa_id' => $inicial->id, 'descricao' => 'Servico legado',
            'valor' => '50.00', 'status' => $statusOs,
        ]);
        $nota = Nota::create([
            'cliente_id' => $cliente->id, 'veiculo_cliente_id' => $veiculoCliente,
            'tipo' => 'Servico', 'status' => $statusNota, 'subtotal' => 50,
            'desconto' => 0, 'total' => 50,
        ]);
        NotasItem::create([
            'nota_id' => $nota->id, 'itemable_type' => OrdemServico::class,
            'itemable_id' => $os->id, 'descricao' => 'Servico legado',
            'quantidade' => 1, 'valor_unitario' => 50,
            'desconto' => 0, 'valor_total' => 50,
        ]);
        return [$nota, $os];
    }

    public function test_auditoria_e_somente_leitura_e_correcao_explicita_e_idempotente(): void
    {
        [$nota, $os] = $this->cenario();
        $auditoria = app(AuditarOsVinculadas::class);
        $this->assertSame('corrigivel_com_confirmacao', $auditoria->auditar($nota->id)[0]['situacao']);
        $this->assertSame('aberta', $os->fresh()->status);
        $this->artisan('notas:auditar-os', ['--nota' => $nota->id])->assertExitCode(0);
        $this->assertSame('aberta', $os->fresh()->status);
        $this->artisan('notas:auditar-os', ['--nota' => $nota->id, '--corrigir' => true])->assertExitCode(1);
        $this->assertSame('aberta', $os->fresh()->status);
        $this->artisan('notas:auditar-os', [
            '--nota' => $nota->id, '--corrigir' => true,
            '--confirmar' => (string) $nota->id,
        ])->assertExitCode(0);
        $this->assertSame('finalizada', $os->fresh()->status);
        $this->assertSame('final', $os->fresh()->etapa->tipo);
        $this->assertNotNull($os->fresh()->data_fechamento);
        $this->assertSame(0, $auditoria->corrigirNota($nota->id));
        $this->assertSame('regular', $auditoria->auditar($nota->id)[0]['situacao']);
        $this->assertSame('Finalizado', $nota->fresh()->status);
        $this->assertDatabaseCount('movimentacao_estoques', 0);
    }

    public function test_os_vinculada_mais_de_uma_vez_exige_revisao(): void
    {
        [$nota, $os] = $this->cenario();
        NotasItem::create([
            'nota_id' => $nota->id, 'itemable_type' => OrdemServico::class,
            'itemable_id' => $os->id, 'descricao' => 'Duplicidade legado',
            'quantidade' => 1, 'valor_unitario' => 50, 'desconto' => 0, 'valor_total' => 50,
        ]);
        $auditoria = app(AuditarOsVinculadas::class);
        $this->assertSame('vinculo_duplicado', $auditoria->auditar($nota->id)[0]['situacao']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicado');
        $auditoria->corrigirNota($nota->id);
    }

    public function test_nota_cancelada_nao_dispara_correcao_automatica(): void
    {
        [$nota, $os] = $this->cenario('Cancelado');
        $auditoria = app(AuditarOsVinculadas::class);
        $this->assertSame('revisao_manual', $auditoria->auditar($nota->id)[0]['situacao']);
        try {
            $auditoria->corrigirNota($nota->id);
            $this->fail('Nota cancelada nao pode reconciliar automaticamente.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('finalizada', $e->getMessage());
        }
        $this->assertSame('aberta', $os->fresh()->status);
    }

    public function test_os_cancelada_bloqueia_reconciliacao(): void
    {
        [$nota, $os] = $this->cenario('Finalizado', 'cancelada');
        $auditoria = app(AuditarOsVinculadas::class);
        $this->assertSame('revisao_manual', $auditoria->auditar($nota->id)[0]['situacao']);
        try {
            $auditoria->corrigirNota($nota->id);
            $this->fail('OS cancelada nao pode ser finalizada pela auditoria.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('bloqueado', $e->getMessage());
        }
        $this->assertSame('cancelada', $os->fresh()->status);
    }

    public function test_cliente_divergente_exige_revisao_manual(): void
    {
        [$nota, $os] = $this->cenario();
        $outro = Cliente::create([
            'pessoa_id' => Pessoa::create(['nome' => 'Outro Cliente'])->id,
            'pontos' => 0,
        ]);
        $os->update(['cliente_id' => $outro->id]);
        $auditoria = app(AuditarOsVinculadas::class);
        $this->assertSame('cliente_ou_veiculo_divergente', $auditoria->auditar($nota->id)[0]['situacao']);
        try {
            $auditoria->corrigirNota($nota->id);
            $this->fail('Vinculo inconsistente nao pode alterar OS.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('divergente', $e->getMessage());
        }
        $this->assertSame('aberta', $os->fresh()->status);
    }
}
