<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PainelForgeTest extends TestCase
{
    use RefreshDatabase;

    private ?string $temporario = null;

    protected function tearDown(): void
    {
        if ($this->temporario !== null && is_dir($this->temporario)) {
            foreach (scandir($this->temporario) ?: [] as $nome) {
                if ($nome === '.' || $nome === '..') {
                    continue;
                }
                $dir = $this->temporario.'/'.$nome;
                if (is_link($dir)) {
                    unlink($dir);
                } elseif (is_dir($dir)) {
                    @unlink($dir.'/manifesto.json');
                    @rmdir($dir);
                }
            }
            @rmdir($this->temporario);
        }
        parent::tearDown();
    }

    private function raizTeste(): string
    {
        $this->temporario = sys_get_temp_dir().'/forge-painel-'.bin2hex(random_bytes(12));
        mkdir($this->temporario, 0700);
        config()->set('forge.staging_root', $this->temporario);

        return $this->temporario;
    }

    private function usuario(string $perfil): User
    {
        return User::factory()->create([
            'perfil' => $perfil,
            'status' => true,
        ]);
    }

    public function test_visitante_nao_acessa_painel(): void
    {
        $this->get(route('admin.forge.index'))
            ->assertRedirect(route('erro-autenticacao'));
    }

    public function test_cliente_e_colaborador_nao_acessam_painel(): void
    {
        foreach ([User::PERFIL_CLIENTE, User::PERFIL_COLABORADOR] as $perfil) {
            $this->actingAs($this->usuario($perfil))
                ->get(route('admin.forge.index'))
                ->assertForbidden();
        }
    }

    public function test_admin_ve_aviso_quando_staging_esta_indisponivel(): void
    {
        $raiz = $this->raizTeste();
        config()->set('forge.staging_root', $raiz.'/inexistente');

        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->get(route('admin.forge.index'))
            ->assertOk()
            ->assertSee('Staging privado indisponível');
    }

    public function test_admin_consulta_pacote_sem_afirmar_que_esta_validado(): void
    {
        $raiz = $this->raizTeste();
        $nome = 'pronto-20261110T120224837022Z';
        mkdir($raiz.'/'.$nome, 0700);
        file_put_contents($raiz.'/'.$nome.'/manifesto.json', '{}');

        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->get(route('admin.forge.index'))
            ->assertOk()
            ->assertSee($nome)
            ->assertSee('Manifesto presente (não validado)')
            ->assertSee('não importa dados');
    }

    public function test_painel_nao_oferece_endpoint_de_importacao(): void
    {
        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->post(route('admin.forge.index'))
            ->assertStatus(405);
    }
}
