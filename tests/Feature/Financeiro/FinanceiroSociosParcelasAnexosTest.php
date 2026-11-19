<?php

namespace Tests\Feature\Financeiro;

use App\Actions\FinanceiroSocios\AtualizarParcelasContaPagarSocio;
use App\Actions\FinanceiroSocios\CriarContaPagarSocio;
use App\Actions\FinanceiroSocios\RegistrarPagamentoSocio;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class FinanceiroSociosParcelasAnexosTest extends TestCase
{
    use RefreshDatabase;

    private function conta(string $valor = '300.00'): array
    {
        $socio = Socio::create(['nome' => 'Socio S01']);
        $conta = app(CriarContaPagarSocio::class)->execute($socio, [
            'descricao' => 'Cartao de credito pessoal', 'categoria' => 'Cartao de credito',
            'valor' => $valor, 'data_emissao' => '2026-11-01',
            'primeiro_vencimento' => '2026-11-30', 'quantidade_parcelas' => 3,
        ]);
        return [$socio, $conta];
    }

    private function linhas($conta, array $valores = ['80.00', '120.00', '100.00']): array
    {
        return $conta->parcelas()->orderBy('numero')->get()->values()->map(
            fn ($p, $i) => [
                'id' => $p->id, 'valor' => $valores[$i],
                'data_vencimento' => $p->data_vencimento->format('Y-m-d'),
            ]
        )->all();
    }

    public function test_criar_parcelas_personalizadas_sem_alterar_geracao_antiga(): void
    {
        $socio = Socio::create(['nome' => 'Socio S01']);
        $conta = app(CriarContaPagarSocio::class)->execute($socio, [
            'descricao' => 'Despesas pessoais', 'categoria' => 'Outros',
            'valor' => '300.00', 'data_emissao' => '2026-11-01',
            'primeiro_vencimento' => '2026-11-30', 'quantidade_parcelas' => 3,
            'personalizar_parcelas' => true,
            'parcelas' => [
                ['valor' => '80.00', 'data_vencimento' => '2026-11-15'],
                ['valor' => '120.00', 'data_vencimento' => '2026-12-20'],
                ['valor' => '100.00', 'data_vencimento' => '2027-01-25'],
            ],
        ]);
        $this->assertSame(['80.00', '120.00', '100.00'], $conta->parcelas->pluck('valor')->all());
        $this->assertSame('300.00', $conta->valor);
        [$outraPessoa, $igual] = $this->conta();
        $this->assertSame(['100.00', '100.00', '100.00'], $igual->parcelas->pluck('valor')->all());
    }

    public function test_personalizacao_com_soma_incorreta_faz_rollback(): void
    {
        $socio = Socio::create(['nome' => 'Socio S01']);
        $this->expectException(InvalidArgumentException::class);
        try {
            app(CriarContaPagarSocio::class)->execute($socio, [
                'descricao' => 'Teste', 'categoria' => 'Outros',
                'valor' => '300.00', 'data_emissao' => '2026-11-01',
                'primeiro_vencimento' => '2026-11-30', 'quantidade_parcelas' => 2,
                'personalizar_parcelas' => true,
                'parcelas' => [
                    ['valor' => '80.00', 'data_vencimento' => '2026-11-15'],
                    ['valor' => '100.00', 'data_vencimento' => '2026-12-20'],
                ],
            ]);
        } finally {
            $this->assertDatabaseCount('contas_pagar_socios', 0);
        }
    }

    public function test_editar_parcelas_salva_historico_sem_mudar_total(): void
    {
        [$socio, $conta] = $this->conta();
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $alteradas = app(AtualizarParcelasContaPagarSocio::class)->execute($conta, [
            'parcelas' => $this->linhas($conta), 'motivo' => 'Parcelas do contrato corrigidas.',
        ], $admin->id);
        $this->assertSame(3, $alteradas);
        $this->assertSame('300.00', $conta->fresh()->valor);
        $this->assertSame(['80.00', '120.00', '100.00'], $conta->fresh()->parcelas->pluck('valor')->all());
        $this->assertDatabaseCount('parcelas_contas_pagar_socios_ajustes', 3);
        $this->assertSame(0, app(AtualizarParcelasContaPagarSocio::class)->execute($conta, [
            'parcelas' => $this->linhas($conta), 'motivo' => 'Sem novas alteracoes de valores.',
        ], $admin->id));
        $this->assertDatabaseCount('parcelas_contas_pagar_socios_ajustes', 3);
    }

    public function test_parcela_pago_nao_pode_cair_abaixo_do_valor_pago(): void
    {
        [$socio, $conta] = $this->conta();
        app(RegistrarPagamentoSocio::class)->execute($conta, [
            'parcela_id' => $conta->parcelas()->firstOrFail()->id,
            'valor' => '70.00', 'origem' => 'pessoal', 'data_pagamento' => '2026-11-04',
        ]);
        $this->expectException(InvalidArgumentException::class);
        try {
            app(AtualizarParcelasContaPagarSocio::class)->execute($conta, [
                'parcelas' => $this->linhas($conta, ['50.00', '150.00', '100.00']),
                'motivo' => 'Tentativa menor que pagamento ativo.',
            ]);
        } finally {
            $this->assertSame('100.00', $conta->parcelas()->firstOrFail()->fresh()->valor);
            $this->assertDatabaseCount('parcelas_contas_pagar_socios_ajustes', 0);
            $this->assertDatabaseCount('pagamentos_contas_pagar_socios', 1);
        }
    }

    public function test_formulario_edicao_valida_socio_e_permissoes(): void
    {
        [$socio, $conta] = $this->conta();
        $cliente = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $rota = route('socios-financeiro.contas.parcelas.update', [$socio, $conta]);
        $dados = ['parcelas' => $this->linhas($conta), 'motivo' => 'Reajuste confirmado pelo credor.'];
        $this->actingAs($cliente)->patch($rota, $dados)->assertForbidden();
        $this->actingAs($admin)->patch($rota, $dados)->assertRedirect();
        $this->assertDatabaseCount('parcelas_contas_pagar_socios_ajustes', 3);
        $outro = Socio::create(['nome' => 'Outro socio']);
        $this->actingAs($admin)->patch(route('socios-financeiro.contas.parcelas.update', [$outro, $conta]), $dados)->assertNotFound();
    }

    public function test_anexos_privados_com_edicao_e_exclusao_e_acesso_admin(): void
    {
        [$socio, $conta] = $this->conta();
        Storage::fake('private');
        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $cliente = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $url = route('socios-financeiro.contas.anexos.store', [$socio, $conta]);
        $this->actingAs($cliente)->post($url, [
            'arquivo' => UploadedFile::fake()->create('boleto.pdf', 10, 'application/pdf'),
            'tipo' => 'boleto',
        ])->assertForbidden();
        $this->actingAs($admin)->post($url, [
            'arquivo' => UploadedFile::fake()->create('boleto.pdf', 10, 'application/pdf'),
            'tipo' => 'boleto', 'observacoes' => 'Boleto do socio',
        ])->assertRedirect(route('socios-financeiro.contas.show', [$socio, $conta]));
        $vinculo = $conta->anexosVinculos()->firstOrFail();
        $this->assertSame('App\\Models\\ContaPagarSocio', $vinculo->vinculavel_type);
        Storage::disk('private')->assertExists($vinculo->anexo->arquivo);
        $this->actingAs($cliente)->get(route('anexos.download', $vinculo->anexo))->assertForbidden();
        $this->actingAs($admin)->get(route('anexos.download', $vinculo->anexo))->assertOk();
        $this->actingAs($admin)->patch(route('anexos.update', $vinculo), [
            'tipo' => 'comprovante', 'observacoes' => 'Pagamento comprovado',
        ])->assertRedirect();
        $this->assertSame('comprovante', $vinculo->fresh()->tipo);
        $this->actingAs($admin)->delete(route('anexos.destroy', $vinculo))->assertRedirect();
        $this->assertDatabaseCount('anexos_vinculos', 0);
        $this->assertDatabaseCount('anexos', 0);
    }
}
