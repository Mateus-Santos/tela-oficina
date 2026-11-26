<?php

namespace Tests\Feature\Notas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeuxNotasHabilitacaoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
    }

    public function test_desabilitada_bloqueia_todas_as_rotas_vue_inclusive_mutacoes(): void
    {
        config()->set('feux_notas.central_vue3', false);
        $this->actingAs($this->admin());

        $this->get(route('notas.workspace'))->assertNotFound();
        $this->getJson(route('notas.vue.dados'))->assertNotFound();
        $this->getJson(route('notas.vue.opcoes'))->assertNotFound();
        $this->getJson(route('notas.vue.etapas.lote.preview'))->assertNotFound();
        $this->getJson(route('notas.vue.detalhe', ['nota' => 999999]))->assertNotFound();
        $this->postJson(route('notas.vue.criar'), [])->assertNotFound();
        $this->postJson(route('notas.vue.etapas.lote.executar'), [])->assertNotFound();
        $this->putJson(route('notas.vue.editar', ['nota' => 999999]), [])->assertNotFound();
        $this->patchJson(route('notas.workspace.move', ['nota' => 999999]), [])->assertNotFound();
        $this->postJson(route('notas.vue.finalizar', ['nota' => 999999]), [])->assertNotFound();
        $this->postJson(route('notas.vue.cancelar', ['nota' => 999999]), [])->assertNotFound();
        $this->deleteJson(route('notas.vue.excluir', ['nota' => 999999]))->assertNotFound();
    }

    public function test_desabilitada_mantem_link_legado_e_redirecionamento_compatibilidade(): void
    {
        config()->set('feux_notas.central_vue3', false);
        $this->actingAs($this->admin());

        $this->get(route('notasitem.index'))->assertRedirect(route('notas.index'));
        $this->get(route('notas.index'))->assertOk()
            ->assertSee('href="'.route('notasitem.index').'"', false)
            ->assertDontSee('href="'.route('notas.workspace').'"', false);
    }

    public function test_habilitada_admin_acessa_vue_e_menu_aponta_para_vue(): void
    {
        config()->set('feux_notas.central_vue3', true);
        $this->actingAs($this->admin());

        $this->getJson(route('notas.vue.dados'))->assertOk();
        $this->get(route('notas.index'))->assertOk()
            ->assertSee('href="'.route('notas.workspace').'"', false);
    }

    public function test_habilitada_cliente_continua_sem_acesso_administrativo(): void
    {
        config()->set('feux_notas.central_vue3', true);
        $cliente = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $this->actingAs($cliente)
            ->getJson(route('notas.vue.dados'))->assertForbidden();
    }
}
