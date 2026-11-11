<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Forge\RelatoriosValidacaoForge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HistoricoForgeTest extends TestCase
{
    use RefreshDatabase;

    private ?string $raiz = null;
    private string $pacote = 'pronto-20261110T120224837022Z';

    protected function tearDown(): void
    {
        if ($this->raiz !== null) {
            $this->limpar($this->raiz);
        }
        parent::tearDown();
    }

    public function test_admin_visualiza_relatorio_historico_sem_validar_no_http(): void
    {
        $raiz = $this->preparar();
        $relatorios = new RelatoriosValidacaoForge();
        $relatorios->registrar($raiz, $this->pacote, [
            'manifesto_sha256' => hash_file('sha256', $raiz.'/'.$this->pacote.'/manifesto.json'),
            'arquivos' => [],
            'relacoes' => [],
            'alertas' => [],
        ]);

        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $this->actingAs($admin)
            ->get(route('admin.forge.index'))
            ->assertOk()
            ->assertSee('Validação anterior: aprovada')
            ->assertSee('Resultado histórico')
            ->assertSee('não substitui uma nova verificação');
    }

    public function test_reprovacao_e_exibida_sem_expor_detalhes_tecnicos(): void
    {
        $raiz = $this->preparar();
        (new RelatoriosValidacaoForge())->registrar($raiz, $this->pacote, null);

        $admin = User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
        $this->actingAs($admin)
            ->get(route('admin.forge.index'))
            ->assertOk()
            ->assertSee('Validação anterior: reprovada')
            ->assertDontSee('RuntimeException');
    }

    public function test_cliente_nao_ve_historico(): void
    {
        $raiz = $this->preparar();
        (new RelatoriosValidacaoForge())->registrar($raiz, $this->pacote, null);
        $cliente = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $this->actingAs($cliente)
            ->get(route('admin.forge.index'))
            ->assertForbidden();
    }

    private function preparar(): string
    {
        $this->raiz = sys_get_temp_dir().'/forge-painel-historico-'.bin2hex(random_bytes(8));
        mkdir($this->raiz, 0700);
        mkdir($this->raiz.'/'.$this->pacote, 0700);
        file_put_contents($this->raiz.'/'.$this->pacote.'/manifesto.json', '{}');
        config()->set('forge.staging_root', $this->raiz);
        return $this->raiz;
    }

    private function limpar(string $dir): void
    {
        if (is_link($dir)) {
            unlink($dir);
            return;
        }
        if (!is_dir($dir)) {
            return;
        }
        @chmod($dir, 0700);
        foreach (scandir($dir) ?: [] as $nome) {
            if ($nome === '.' || $nome === '..') {
                continue;
            }
            $caminho = $dir.'/'.$nome;
            if (is_dir($caminho) && !is_link($caminho)) {
                $this->limpar($caminho);
            } else {
                @unlink($caminho);
            }
        }
        @rmdir($dir);
    }
}
