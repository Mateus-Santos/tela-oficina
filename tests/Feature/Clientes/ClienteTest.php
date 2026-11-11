<?php

namespace Tests\Feature\Clientes;

use App\Actions\OrdemServico\CriarOrdemServico;
use App\Models\Cliente;
use App\Models\Pessoa;
use App\Models\SetorServico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => 1,
        ]);
    }

    private function criarCliente(
        array $dadosPessoa = [],
        array $dadosCliente = []
    ): Cliente {
        $pessoa = Pessoa::create(
            array_merge(
                [
                    'nome' => 'Cliente Teste',
                    'cpf' => null,
                    'rg' => null,
                    'data_nascimento' => null,
                    'telefone_1' => null,
                    'telefone_2' => null,
                ],
                $dadosPessoa
            )
        );

        return Cliente::create(
            array_merge(
                [
                    'pessoa_id' => $pessoa->id,
                    'pontos' => 0,
                ],
                $dadosCliente
            )
        );
    }

    public function test_cria_cliente_apenas_com_nome(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('clientes.store'),
                [
                    'nome' => 'Maria da Silva',
                ]
            );

        $response->assertRedirect(
            route('clientes.index')
        );

        $this->assertDatabaseHas(
            'pessoas',
            [
                'nome' => 'Maria da Silva',
                'cpf' => null,
                'rg' => null,
                'data_nascimento' => null,
                'telefone_1' => null,
                'telefone_2' => null,
            ]
        );

        $pessoa = Pessoa::where(
            'nome',
            'Maria da Silva'
        )->firstOrFail();

        $this->assertDatabaseHas(
            'clientes',
            [
                'pessoa_id' => $pessoa->id,
                'pontos' => 0,
            ]
        );
    }

    public function test_cria_cliente_com_campos_opcionais_vazios(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('clientes.store'),
                [
                    'nome' => 'Cliente Sem Contato',
                    'cpf' => '',
                    'rg' => '',
                    'data_nascimento' => '',
                    'telefone_1' => '',
                    'telefone_2' => '',
                    'email' => '',
                    'pontos' => '',
                ]
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'pessoas',
            [
                'nome' => 'Cliente Sem Contato',
                'cpf' => null,
                'rg' => null,
                'data_nascimento' => null,
                'telefone_1' => null,
                'telefone_2' => null,
            ]
        );
    }

    public function test_atualiza_cliente_apenas_com_nome(): void
    {
        $cliente = $this->criarCliente();

        $response = $this
            ->actingAs($this->admin)
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Nome Atualizado',
                ]
            );

        $response->assertRedirect(
            route('clientes.index')
        );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'pessoas',
            [
                'id' => $cliente->pessoa_id,
                'nome' => 'Nome Atualizado',
            ]
        );

        $this->assertDatabaseHas(
            'clientes',
            [
                'id' => $cliente->id,
                'pontos' => 0,
            ]
        );
    }

    public function test_permite_remover_dados_opcionais_existentes(): void
    {
        $cliente = $this->criarCliente([
            'cpf' => '12345678901',
            'rg' => '123456789',
            'data_nascimento' => '1990-01-10',
            'telefone_1' => '75999999999',
            'telefone_2' => '7533334444',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Cliente Atualizado',
                    'cpf' => '',
                    'rg' => '',
                    'data_nascimento' => '',
                    'telefone_1' => '',
                    'telefone_2' => '',
                    'pontos' => 0,
                ]
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'pessoas',
            [
                'id' => $cliente->pessoa_id,
                'nome' => 'Cliente Atualizado',
                'cpf' => null,
                'rg' => null,
                'data_nascimento' => null,
                'telefone_1' => null,
                'telefone_2' => null,
            ]
        );
    }

    public function test_nao_permite_cpf_duplicado_na_atualizacao(): void
    {
        $this->criarCliente([
            'nome' => 'Primeiro Cliente',
            'cpf' => '12345678901',
        ]);

        $cliente = $this->criarCliente([
            'nome' => 'Segundo Cliente',
            'cpf' => '98765432100',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->from(
                route(
                    'clientes.edit',
                    $cliente->id
                )
            )
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Segundo Cliente',
                    'cpf' => '123.456.789-01',
                    'pontos' => 0,
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'clientes.edit',
                    $cliente->id
                )
            )
            ->assertSessionHasErrors('cpf');

        $this->assertDatabaseHas(
            'pessoas',
            [
                'id' => $cliente->pessoa_id,
                'cpf' => '98765432100',
            ]
        );
    }

    public function test_atualiza_email_quando_cliente_possui_usuario(): void
    {
        $cliente = $this->criarCliente([
            'nome' => 'Cliente com Usuário',
        ]);

        $usuario = User::factory()->create([
            'pessoa_id' => $cliente->pessoa_id,
            'email' => 'antigo@example.com',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Cliente com Usuário',
                    'email' => 'novo@example.com',
                    'pontos' => 0,
                ]
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $usuario->id,
                'email' => 'novo@example.com',
            ]
        );
    }

    public function test_cliente_sem_usuario_nao_cria_usuario_ao_atualizar(): void
    {
        $cliente = $this->criarCliente([
            'nome' => 'Cliente sem Usuário',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Cliente sem Usuário',
                    'email' => 'ignorado@example.com',
                    'pontos' => 0,
                ]
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing(
            'users',
            [
                'pessoa_id' => $cliente->pessoa_id,
            ]
        );
    }

    public function test_cria_usuario_de_acesso_sem_coluna_name(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('clientes.store'),
                [
                    'nome' => 'Cliente com Acesso',
                    'email' => 'cliente@example.com',
                    'criar_usuario' => 1,
                ]
            );

        $response->assertSessionHasNoErrors();

        $pessoa = Pessoa::where(
            'nome',
            'Cliente com Acesso'
        )->firstOrFail();

        $this->assertDatabaseHas(
            'users',
            [
                'pessoa_id' => $pessoa->id,
                'email' => 'cliente@example.com',
            ]
        );
    }


    public function test_cria_cliente_com_documentos_mascarados(): void
    {
        $this->actingAs($this->admin)
            ->post(route('clientes.store'), [
                'nome' => 'Cliente Normalizado',
                'cpf' => '123.456.789-01',
                'rg' => '12.345.678',
                'telefone_1' => '(75) 99999-8888',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pessoas', [
            'nome' => 'Cliente Normalizado',
            'cpf' => '12345678901',
            'rg' => '12345678',
            'telefone_1' => '75999998888',
        ]);
    }

    public function test_rejeita_pessoa_inexistente_sem_nome(): void
    {
        $this->actingAs($this->admin)
            ->post(route('clientes.store'), ['pessoa_id' => 999999999])
            ->assertSessionHasErrors('pessoa_id');
    }


    public function test_rejeita_documentos_enviados_como_arrays(): void
    {
        $this->actingAs($this->admin)
            ->post(route('clientes.store'), [
                'nome' => 'Cliente Entrada Invalida',
                'cpf' => ['12345678901'],
                'telefone_1' => ['75999998888'],
            ])
            ->assertSessionHasErrors(['cpf', 'telefone_1']);

        $this->assertDatabaseMissing('pessoas', [
            'nome' => 'Cliente Entrada Invalida',
        ]);
    }


    public function test_cadastro_com_usuario_preserva_senha_temporaria(): void
    {
        $resposta = $this->actingAs($this->admin)
            ->post(route('clientes.store'), [
                'nome' => 'Cliente com Senha',
                'email' => 'senha-cliente@example.com',
                'criar_usuario' => 1,
            ]);

        $resposta->assertRedirect(route('clientes.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('senha_temporaria')
            ->assertSessionHas('email_usuario', 'senha-cliente@example.com');

        $senha = session('senha_temporaria');
        $usuario = User::where('email', 'senha-cliente@example.com')->firstOrFail();

        $this->assertIsString($senha);
        $this->assertTrue(Hash::check($senha, $usuario->password));
        $this->assertSame(User::PERFIL_CLIENTE, $usuario->perfil);
    }

    public function test_email_sem_criar_usuario_nao_gera_acesso(): void
    {
        $this->actingAs($this->admin)
            ->post(route('clientes.store'), [
                'nome' => 'Cliente Apenas Contato',
                'email' => 'contato-cliente@example.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pessoas', [
            'nome' => 'Cliente Apenas Contato',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'contato-cliente@example.com',
        ]);
    }


    public function test_admin_atualiza_cliente_com_documentos_mascarados(): void
    {
        $cliente = $this->criarCliente();

        $this->actingAs($this->admin)
            ->put(route('clientes.update', $cliente->id), [
                'nome' => 'Cliente Normalizado',
                'cpf' => '123.456.789-01',
                'rg' => '12.345.678',
                'telefone_1' => '(75) 99999-8888',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pessoas', [
            'id' => $cliente->pessoa_id,
            'nome' => 'Cliente Normalizado',
            'cpf' => '12345678901',
            'rg' => '12345678',
            'telefone_1' => '75999998888',
        ]);
    }

    public function test_update_rejeita_documentos_em_arrays(): void
    {
        $cliente = $this->criarCliente();

        $this->actingAs($this->admin)
            ->put(route('clientes.update', $cliente->id), [
                'nome' => 'Alteracao Invalida',
                'cpf' => ['12345678901'],
                'telefone_1' => ['75999998888'],
            ])
            ->assertSessionHasErrors(['cpf', 'telefone_1']);

        $this->assertSame(
            'Cliente Teste',
            $cliente->pessoa->fresh()->nome
        );
    }

    public function test_cliente_nao_altera_pontos_pela_rota_administrativa(): void
    {
        $cliente = $this->criarCliente([], ['pontos' => 10]);

        $usuario = User::factory()->create([
            'pessoa_id' => $cliente->pessoa_id,
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

        $this->actingAs($usuario)
            ->put(route('clientes.update', $cliente->id), [
                'nome' => 'Tentativa de Alteracao',
                'pontos' => 999999,
            ])
            ->assertForbidden();

        $this->assertEquals(10, $cliente->fresh()->pontos);
    }


    public function test_atualiza_pessoa_pontos_e_email_na_mesma_operacao(): void
    {
        $cliente = $this->criarCliente();

        $usuario = User::factory()->create([
            'pessoa_id' => $cliente->pessoa_id,
            'email' => 'antes-action@example.com',
        ]);

        $this->actingAs($this->admin)
            ->put(route('clientes.update', $cliente->id), [
                'nome' => 'Cliente Atualizado pela Action',
                'cpf' => '123.456.789-01',
                'telefone_1' => '(75) 99999-8888',
                'pontos' => 25,
                'email' => 'depois-action@example.com',
            ])
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pessoas', [
            'id' => $cliente->pessoa_id,
            'nome' => 'Cliente Atualizado pela Action',
            'cpf' => '12345678901',
            'telefone_1' => '75999998888',
        ]);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'pontos' => 25,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'email' => 'depois-action@example.com',
        ]);
    }

    public function test_atualizacao_nao_modifica_perfil_ou_status_do_usuario(): void
    {
        $cliente = $this->criarCliente();

        $usuario = User::factory()->create([
            'pessoa_id' => $cliente->pessoa_id,
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('clientes.update', $cliente->id), [
                'nome' => 'Cliente com Permissoes Preservadas',
                'email' => $usuario->email,
                'perfil' => User::PERFIL_ADMIN,
                'status' => false,
                'pontos' => 0,
            ])
            ->assertSessionHasNoErrors();

        $usuario->refresh();

        $this->assertSame(
            User::PERFIL_CLIENTE,
            $usuario->perfil
        );

        $this->assertTrue((bool) $usuario->status);
    }


    public function test_falha_no_email_desfaz_pessoa_e_pontos(): void
    {
        $cliente = $this->criarCliente([], ['pontos' => 17]);

        $usuario = User::factory()->create([
            'pessoa_id' => $cliente->pessoa_id,
            'email' => 'rollback-original@example.com',
        ]);

        $outraConta = User::factory()->create([
            'email' => 'rollback-ocupado@example.com',
        ]);

        $nomeOriginal = $cliente->pessoa->nome;
        $pontosOriginais = (int) $cliente->pontos;
        $emailOriginal = $usuario->email;

        $this->assertNotSame(
            $emailOriginal,
            $outraConta->email
        );

        try {
            app(\App\Actions\Clientes\AtualizarCliente::class)->execute(
                $cliente,
                $usuario,
                [
                    'nome' => 'Nome Que Deve Ser Revertido',
                    'cpf' => null,
                    'rg' => null,
                    'data_nascimento' => null,
                    'telefone_1' => null,
                    'telefone_2' => null,
                    'pontos' => 999,
                    'email' => $outraConta->email,
                ]
            );

            $this->fail(
                'Era esperada rejeicao do email duplicado.'
            );
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame(
                $nomeOriginal,
                $cliente->pessoa->fresh()->nome
            );

            $this->assertSame(
                $pontosOriginais,
                (int) $cliente->fresh()->pontos
            );

            $this->assertSame(
                $emailOriginal,
                $usuario->fresh()->email
            );

            $this->assertSame(
                $outraConta->email,
                $outraConta->fresh()->email
            );
        }
    }


    public function test_admin_exclui_cliente_sem_vinculos_e_preserva_pessoa(): void
    {
        $cliente = $this->criarCliente();
        $pessoaId = $cliente->pessoa_id;

        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $cliente->id))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('pessoas', [
            'id' => $pessoaId,
        ]);
    }

    public function test_admin_nao_exclui_cliente_com_nota(): void
    {
        $cliente = $this->criarCliente();

        $notaId = DB::table('notas')->insertGetId([
            'cliente_id' => $cliente->id,
            'tipo' => 'servico',
            'status' => 'Aberto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $cliente->id))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('notas', [
            'id' => $notaId,
            'cliente_id' => $cliente->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertSee(
                'Este cliente possui vínculos cadastrados e não pode ser excluído.'
            );
    }

    public function test_admin_nao_exclui_cliente_com_conta_receber(): void
    {
        $cliente = $this->criarCliente();

        $contaId = DB::table('contas_receber')->insertGetId([
            'cliente_id' => $cliente->id,
            'descricao' => 'Conta de teste de exclusao',
            'valor_original' => 100,
            'data_vencimento' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $cliente->id))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('contas_receber', [
            'id' => $contaId,
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_admin_nao_exclui_cliente_com_usuario(): void
    {
        $cliente = $this->criarCliente();

        $usuario = User::factory()->create([
            'pessoa_id' => $cliente->pessoa_id,
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $cliente->id))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'pessoa_id' => $cliente->pessoa_id,
        ]);
    }


    private function criarVeiculoParaProtecaoExclusao(
        Cliente $cliente,
        string $placa
    ): int {
        $agora = now();

        $montadoraId = DB::table('montadoras')
            ->insertGetId([
                'nome' => 'Montadora Teste Exclusao',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

        $veiculoId = DB::table('veiculos')
            ->insertGetId([
                'nome' => 'Modelo Teste Exclusao',
                'montadora_id' => $montadoraId,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

        $veiculoClienteId = DB::table('veiculos_clientes')
            ->insertGetId([
                'cliente_id' => $cliente->id,
                'veiculo_id' => $veiculoId,
                'placa' => $placa,
                'ano' => 2024,
                'cor' => 'Branco',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

        DB::table('cliente_veiculo_cliente')->insert([
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => $veiculoClienteId,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        return $veiculoClienteId;
    }

    public function test_admin_nao_exclui_cliente_com_veiculo(): void
    {
        $cliente = $this->criarCliente();

        $veiculoId = $this->criarVeiculoParaProtecaoExclusao(
            $cliente,
            'EXC1A23'
        );

        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $cliente->id))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('veiculos_clientes', [
            'id' => $veiculoId,
            'cliente_id' => $cliente->id,
        ]);

        $this->assertDatabaseHas('cliente_veiculo_cliente', [
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => $veiculoId,
        ]);
    }

    public function test_admin_nao_exclui_cliente_com_veiculo_compartilhado(): void
    {
        $proprietario = $this->criarCliente([
            'nome' => 'Proprietario Teste',
        ]);

        $compartilhado = $this->criarCliente([
            'nome' => 'Cliente Compartilhado',
        ]);

        $veiculoId = $this->criarVeiculoParaProtecaoExclusao(
            $proprietario,
            'EXC2B34'
        );

        DB::table('cliente_veiculo_cliente')->insert([
            'cliente_id' => $compartilhado->id,
            'veiculo_cliente_id' => $veiculoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseMissing('veiculos_clientes', [
            'cliente_id' => $compartilhado->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $compartilhado->id))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', [
            'id' => $compartilhado->id,
        ]);

        $this->assertDatabaseHas('cliente_veiculo_cliente', [
            'cliente_id' => $compartilhado->id,
            'veiculo_cliente_id' => $veiculoId,
        ]);

        $this->assertDatabaseHas('veiculos_clientes', [
            'id' => $veiculoId,
            'cliente_id' => $proprietario->id,
        ]);
    }

    public function test_admin_nao_exclui_cliente_com_os_historica(): void
    {
        $clienteHistorico = $this->criarCliente([
            'nome' => 'Cliente Historico',
        ]);

        $novoResponsavel = $this->criarCliente([
            'nome' => 'Novo Responsavel',
        ]);

        $veiculoId = $this->criarVeiculoParaProtecaoExclusao(
            $clienteHistorico,
            'EXC3C45'
        );

        $setor = SetorServico::create([
            'setor' => 'Mecânica',
            'nivel' => 1,
        ]);

        $ordem = app(CriarOrdemServico::class)->execute([
            'cliente_id' => $clienteHistorico->id,
            'veiculo_cliente_id' => $veiculoId,
            'setor_servico_id' => $setor->id,
            'descricao' => 'Servico historico de teste',
            'valor' => '150,00',
        ]);

        // Simula a troca posterior de responsavel pelo veiculo.
        // A OS permanece associada ao cliente historico.
        DB::table('cliente_veiculo_cliente')
            ->where('cliente_id', $clienteHistorico->id)
            ->where('veiculo_cliente_id', $veiculoId)
            ->delete();

        DB::table('veiculos_clientes')
            ->where('id', $veiculoId)
            ->update([
                'cliente_id' => $novoResponsavel->id,
            ]);

        DB::table('cliente_veiculo_cliente')->insert([
            'cliente_id' => $novoResponsavel->id,
            'veiculo_cliente_id' => $veiculoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseMissing('veiculos_clientes', [
            'cliente_id' => $clienteHistorico->id,
        ]);

        $this->assertDatabaseMissing('cliente_veiculo_cliente', [
            'cliente_id' => $clienteHistorico->id,
        ]);

        $this->assertDatabaseHas('ordem_servicos', [
            'id' => $ordem->id,
            'cliente_id' => $clienteHistorico->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $clienteHistorico->id))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', [
            'id' => $clienteHistorico->id,
        ]);

        $this->assertDatabaseHas('ordem_servicos', [
            'id' => $ordem->id,
            'cliente_id' => $clienteHistorico->id,
        ]);

        $this->assertDatabaseHas('veiculos_clientes', [
            'id' => $veiculoId,
            'cliente_id' => $novoResponsavel->id,
        ]);
    }
}
