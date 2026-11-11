<?php

namespace Tests\Feature\Marketing;

use App\Models\MarketingCadastroPendente;
use App\Models\MarketingLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrivacidadeDadosMarketingTest extends TestCase
{
    use RefreshDatabase;

    private function pedido(): array
    {
        return [
            'nome' => 'Visitante de teste',
            'whatsapp' => '75999990000',
            'peca' => 'Filtro de oleo',
            'aceite_privacidade' => '1',
        ];
    }

    private function criarLead(string $protocolo, string $ip, int $idadeDias): MarketingLead
    {
        $lead = MarketingLead::query()->create([
            'protocolo' => $protocolo,
            'nome' => 'Retencao teste',
            'whatsapp' => '75999990000',
            'peca' => 'Filtro de oleo',
            'consentido_em' => now(),
            'status' => 'novo',
        ]);

        DB::table('marketing_leads')->where('id', $lead->id)->update([
            'ip_criptografado' => Crypt::encryptString($ip),
            'created_at' => now()->subDays($idadeDias),
        ]);

        return $lead->fresh();
    }

    private function criarPendente(string $email, int $diasAposExpiracao): MarketingCadastroPendente
    {
        return MarketingCadastroPendente::query()->create([
            'nome' => 'Cadastro Pendente',
            'email' => $email,
            'whatsapp' => '75999990000',
            'senha_hash' => 'hash-ficticio-nao-utilizado',
            'token_hash' => str_repeat('a', 64),
            'expira_em' => now()->subDays($diasAposExpiracao),
            'consentido_em' => now()->subDays($diasAposExpiracao + 1),
        ]);
    }

    public function test_ip_nao_e_coletado_por_padrao_e_aceite_continua_obrigatorio(): void
    {
        config()->set('marketing.captura_ip_habilitada', false);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.29'])
            ->post('/pecas/orcamento', $this->pedido())
            ->assertRedirect(route('marketing.pecas.orcamento'));

        $lead = MarketingLead::query()->firstOrFail();
        $this->assertNull($lead->ip_criptografado);
        $this->assertNotNull($lead->consentido_em);

        $this->post('/pecas/orcamento', array_diff_key($this->pedido(), ['aceite_privacidade' => true]))
            ->assertSessionHasErrors('aceite_privacidade');

        $this->assertDatabaseCount('marketing_leads', 1);
    }

    public function test_ip_opcional_fica_cifrado_e_nao_e_exposto_no_modelo(): void
    {
        config()->set('marketing.captura_ip_habilitada', true);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.29'])
            ->post('/pecas/orcamento', $this->pedido())
            ->assertRedirect(route('marketing.pecas.orcamento'));

        $lead = MarketingLead::query()->firstOrFail();
        $this->assertNotNull($lead->ip_criptografado);
        $this->assertNotSame('198.51.100.29', $lead->ip_criptografado);
        $this->assertSame('198.51.100.29', Crypt::decryptString($lead->ip_criptografado));
        $this->assertArrayNotHasKey('ip_criptografado', $lead->toArray());
    }

    public function test_rotina_sem_executar_e_apenas_simulacao(): void
    {
        $lead = $this->criarLead('MKT-PRIVACIDADE1', '203.0.113.15', 60);
        $pendente = $this->criarPendente('cadastro-antigo@example.test', 15);

        $this->artisan('marketing:retencao')->assertExitCode(0);

        $this->assertNotNull($lead->fresh()->ip_criptografado);
        $this->assertDatabaseHas('marketing_cadastros_pendentes', ['id' => $pendente->id]);
    }

    public function test_limpeza_explicita_preserva_registros_ativos_e_demais_dados(): void
    {
        config()->set('marketing.retencao_ip_dias', 30);
        config()->set('marketing.retencao_pendentes_dias', 7);

        $antigo = $this->criarLead('MKT-PRIVACIDADE2', '203.0.113.20', 45);
        $recente = $this->criarLead('MKT-PRIVACIDADE3', '203.0.113.21', 5);
        $cadastroAntigo = $this->criarPendente('antigo@example.test', 10);
        $cadastroRecente = $this->criarPendente('recente@example.test', 1);

        $this->artisan('marketing:retencao', ['--executar' => true])->assertExitCode(0);

        $this->assertNull($antigo->fresh()->ip_criptografado);
        $this->assertNotNull($recente->fresh()->ip_criptografado);
        $this->assertDatabaseMissing('marketing_cadastros_pendentes', ['id' => $cadastroAntigo->id]);
        $this->assertDatabaseHas('marketing_cadastros_pendentes', ['id' => $cadastroRecente->id]);
        $this->assertDatabaseHas('marketing_leads', [
            'id' => $antigo->id,
            'nome' => 'Retencao teste',
            'status' => 'novo',
        ]);
        $this->assertDatabaseCount('marketing_leads', 2);
    }
}
