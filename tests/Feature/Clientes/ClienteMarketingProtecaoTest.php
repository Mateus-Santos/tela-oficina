<?php

namespace Tests\Feature\Clientes;

use App\Models\Cliente;
use App\Models\MarketingClienteAcesso;
use App\Models\MarketingLead;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ClienteMarketingProtecaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]));
    }

    private function cliente(): Cliente
    {
        $pessoa = Pessoa::create([
            'nome' => 'Cliente Protecao Marketing',
        ]);

        return Cliente::create([
            'pessoa_id' => $pessoa->id,
            'pontos' => 0,
        ]);
    }

    private function lead(Cliente $cliente): MarketingLead
    {
        $lead = MarketingLead::create([
            'protocolo' => 'MKT-SEG-001',
            'nome' => 'Interessado teste',
            'whatsapp' => '75999990000',
            'peca' => 'Filtro',
            'consentido_em' => now(),
            'status' => 'novo',
        ]);

        $lead->forceFill([
            'cliente_id' => $cliente->id,
        ])->save();

        return $lead;
    }

    private function acesso(Cliente $cliente): MarketingClienteAcesso
    {
        return MarketingClienteAcesso::create([
            'cliente_id' => $cliente->id,
            'portal_inicio' => true,
            'solicitacoes' => true,
        ]);
    }

    public function test_rota_bloqueia_exclusao_com_lead(): void
    {
        $cliente = $this->cliente();
        $lead = $this->lead($cliente);

        $this->delete(route('clientes.destroy', $cliente->id))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('marketing_leads', [
            'id' => $lead->id,
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_rota_bloqueia_exclusao_com_acesso(): void
    {
        $cliente = $this->cliente();
        $acesso = $this->acesso($cliente);

        $this->delete(route('clientes.destroy', $cliente->id))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('marketing_cliente_acessos', [
            'id' => $acesso->id,
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_fk_impede_exclusao_direta_com_lead(): void
    {
        $cliente = $this->cliente();
        $lead = $this->lead($cliente);

        try {
            DB::table('clientes')
                ->where('id', $cliente->id)
                ->delete();

            $this->fail('Exclusao direta deveria ser bloqueada.');
        } catch (QueryException $e) {
            $this->assertSame('23000', (string) $e->getCode());
        }

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('marketing_leads', [
            'id' => $lead->id,
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_fk_impede_exclusao_direta_com_acesso(): void
    {
        $cliente = $this->cliente();
        $acesso = $this->acesso($cliente);

        try {
            DB::table('clientes')
                ->where('id', $cliente->id)
                ->delete();

            $this->fail('Exclusao direta deveria ser bloqueada.');
        } catch (QueryException $e) {
            $this->assertSame('23000', (string) $e->getCode());
        }

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('marketing_cliente_acessos', [
            'id' => $acesso->id,
            'cliente_id' => $cliente->id,
        ]);
    }
}
