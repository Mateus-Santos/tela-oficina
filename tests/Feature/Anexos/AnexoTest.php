<?php

namespace Tests\Feature\Anexos;

use App\Models\Anexo;
use App\Models\AnexoVinculo;
use App\Models\CategoriaFinanceira;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\ContaReceber;
use App\Models\Fornecedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnexoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]);
    }

    private function criarFornecedor(): Fornecedor
    {
        $id = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor Anexo ' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Fornecedor::findOrFail($id);
    }

    private function criarCompra(
        string $status
    ): Compra {
        $fornecedor = $this->criarFornecedor();

        return Compra::create([
            'fornecedor_id' => $fornecedor->id,
            'numero_nf' => (string) random_int(100000, 999999),
            'serie_nf' => '1',
            'chave_nf' => null,
            'data_emissao' => now()->toDateString(),
            'data_entrada' => now()->toDateString(),
            'valor_produtos' => 100,
            'desconto' => 0,
            'frete' => 0,
            'outras_despesas' => 0,
            'valor_total' => 100,
            'status' => $status,
            'observacoes' => null,
        ]);
    }

    private function criarCliente(): Cliente
    {
        $pessoaId = DB::table('pessoas')->insertGetId([
            'nome' => 'Cliente Anexo ' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Cliente::create([
            'pessoa_id' => $pessoaId,
        ]);
    }

    private function criarCategoria(): CategoriaFinanceira
    {
        return CategoriaFinanceira::firstOrCreate(
            [
                'nome' => 'Recebimentos Teste',
                'tipo' => 'entrada',
            ],
            [
                'ativo' => true,
            ]
        );
    }

    private function criarContaReceber(
        string $status
    ): ContaReceber {
        $cliente = $this->criarCliente();
        $categoria = $this->criarCategoria();

        return ContaReceber::create([
            'cliente_id' => $cliente->id,
            'nota_id' => null,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => 'Conta para teste de anexos',
            'valor_original' => 100,
            'desconto' => 0,
            'juros' => 0,
            'multa' => 0,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()
                ->addDays(10)
                ->toDateString(),
            'data_quitacao' => $status === 'quitada'
                ? now()->toDateString()
                : null,
            'status' => $status,
            'observacoes' => null,
        ]);
    }

    private function executarCicloAnexoCompra(
        Compra $compra
    ): void {
        $statusOriginal = $compra->status;

        $resposta = $this
            ->actingAs($this->admin())
            ->post(
                route(
                    'compras.anexos.store',
                    $compra
                ),
                [
                    'tipo' => 'nf',
                    'observacoes' => 'Documento inicial',
                    'arquivo' => UploadedFile::fake()
                        ->create(
                            'nota.pdf',
                            20,
                            'application/pdf'
                        ),
                ]
            );

        $resposta->assertRedirect(
            route(
                'compras.show',
                $compra
            )
        );

        $vinculo = AnexoVinculo::query()
            ->where(
                'vinculavel_type',
                Compra::class
            )
            ->where(
                'vinculavel_id',
                $compra->id
            )
            ->firstOrFail();

        $anexo = $vinculo->anexo;

        $this->assertDatabaseHas(
            'anexos_vinculos',
            [
                'id' => $vinculo->id,
                'vinculavel_type' => Compra::class,
                'vinculavel_id' => $compra->id,
                'tipo' => 'nf',
                'observacoes' => 'Documento inicial',
            ]
        );

        Storage::disk('private')
            ->assertExists(
                $anexo->arquivo
            );

        $caminhoInicial = $anexo->arquivo;

        $resposta = $this
            ->actingAs($this->admin())
            ->patch(
                route(
                    'anexos.update',
                    $vinculo
                ),
                [
                    'tipo' => 'comprovante',
                    'observacoes' => 'Documento atualizado',
                ]
            );

        $resposta->assertRedirect(
            route(
                'compras.show',
                $compra
            )
        );

        $this->assertDatabaseHas(
            'anexos_vinculos',
            [
                'id' => $vinculo->id,
                'tipo' => 'comprovante',
                'observacoes' => 'Documento atualizado',
            ]
        );

        Storage::disk('private')
            ->assertExists(
                $caminhoInicial
            );

        $resposta = $this
            ->actingAs($this->admin())
            ->patch(
                route(
                    'anexos.update',
                    $vinculo
                ),
                [
                    'tipo' => 'comprovante',
                    'observacoes' => 'Arquivo substituído',
                    'arquivo' => UploadedFile::fake()
                        ->create(
                            'novo.pdf',
                            30,
                            'application/pdf'
                        ),
                ]
            );

        $resposta->assertRedirect(
            route(
                'compras.show',
                $compra
            )
        );

        $anexoAtualizado = Anexo::findOrFail(
            $anexo->id
        );

        $this->assertNotSame(
            $caminhoInicial,
            $anexoAtualizado->arquivo
        );

        Storage::disk('private')
            ->assertMissing(
                $caminhoInicial
            );

        Storage::disk('private')
            ->assertExists(
                $anexoAtualizado->arquivo
            );

        $novoCaminho =
            $anexoAtualizado->arquivo;

        $resposta = $this
            ->actingAs($this->admin())
            ->delete(
                route(
                    'anexos.destroy',
                    $vinculo
                )
            );

        $resposta->assertRedirect(
            route(
                'compras.show',
                $compra
            )
        );

        $this->assertDatabaseMissing(
            'anexos_vinculos',
            [
                'id' => $vinculo->id,
            ]
        );

        $this->assertDatabaseMissing(
            'anexos',
            [
                'id' => $anexo->id,
            ]
        );

        Storage::disk('private')
            ->assertMissing(
                $novoCaminho
            );

        $this->assertSame(
            $statusOriginal,
            $compra->fresh()->status
        );
    }

    private function executarCicloAnexoContaReceber(
        ContaReceber $conta
    ): void {
        $statusOriginal = $conta->status;

        $resposta = $this
            ->actingAs($this->admin())
            ->post(
                route(
                    'contas-receber.anexos.store',
                    $conta
                ),
                [
                    'tipo' => 'recibo',
                    'observacoes' => 'Anexo da conta',
                    'arquivo' => UploadedFile::fake()
                        ->create(
                            'recibo.pdf',
                            20,
                            'application/pdf'
                        ),
                ]
            );

        $resposta->assertRedirect(
            route(
                'contas-receber.show',
                $conta
            )
        );

        $vinculo = AnexoVinculo::query()
            ->where(
                'vinculavel_type',
                ContaReceber::class
            )
            ->where(
                'vinculavel_id',
                $conta->id
            )
            ->firstOrFail();

        $anexo = $vinculo->anexo;

        $this->assertDatabaseHas(
            'anexos_vinculos',
            [
                'id' => $vinculo->id,
                'vinculavel_type' => ContaReceber::class,
                'vinculavel_id' => $conta->id,
                'tipo' => 'recibo',
                'observacoes' => 'Anexo da conta',
            ]
        );

        Storage::disk('private')
            ->assertExists(
                $anexo->arquivo
            );

        $caminhoInicial = $anexo->arquivo;

        $resposta = $this
            ->actingAs($this->admin())
            ->patch(
                route(
                    'anexos.update',
                    $vinculo
                ),
                [
                    'tipo' => 'comprovante',
                    'observacoes' => 'Conta atualizada',
                    'arquivo' => UploadedFile::fake()
                        ->create(
                            'comprovante.pdf',
                            25,
                            'application/pdf'
                        ),
                ]
            );

        $resposta->assertRedirect(
            route(
                'contas-receber.show',
                $conta
            )
        );

        $anexoAtualizado = Anexo::findOrFail(
            $anexo->id
        );

        $this->assertDatabaseHas(
            'anexos_vinculos',
            [
                'id' => $vinculo->id,
                'tipo' => 'comprovante',
                'observacoes' => 'Conta atualizada',
            ]
        );

        Storage::disk('private')
            ->assertMissing(
                $caminhoInicial
            );

        Storage::disk('private')
            ->assertExists(
                $anexoAtualizado->arquivo
            );

        $novoCaminho =
            $anexoAtualizado->arquivo;

        $resposta = $this
            ->actingAs($this->admin())
            ->delete(
                route(
                    'anexos.destroy',
                    $vinculo
                )
            );

        $resposta->assertRedirect(
            route(
                'contas-receber.show',
                $conta
            )
        );

        $this->assertDatabaseMissing(
            'anexos_vinculos',
            [
                'id' => $vinculo->id,
            ]
        );

        $this->assertDatabaseMissing(
            'anexos',
            [
                'id' => $anexo->id,
            ]
        );

        Storage::disk('private')
            ->assertMissing(
                $novoCaminho
            );

        $this->assertSame(
            $statusOriginal,
            $conta->fresh()->status
        );
    }

    public function test_compra_aprovada_permite_gerenciar_anexos(): void
    {
        Storage::fake('private');

        $this->executarCicloAnexoCompra(
            $this->criarCompra(
                Compra::STATUS_APROVADA
            )
        );
    }

    public function test_compra_cancelada_permite_gerenciar_anexos(): void
    {
        Storage::fake('private');

        $this->executarCicloAnexoCompra(
            $this->criarCompra(
                Compra::STATUS_CANCELADA
            )
        );
    }

    public function test_conta_receber_aberta_permite_gerenciar_anexos(): void
    {
        Storage::fake('private');

        $this->executarCicloAnexoContaReceber(
            $this->criarContaReceber(
                'aberta'
            )
        );
    }

    public function test_conta_receber_quitada_permite_gerenciar_anexos(): void
    {
        Storage::fake('private');

        $this->executarCicloAnexoContaReceber(
            $this->criarContaReceber(
                'quitada'
            )
        );
    }

    public function test_conta_receber_cancelada_permite_gerenciar_anexos(): void
    {
        Storage::fake('private');

        $this->executarCicloAnexoContaReceber(
            $this->criarContaReceber(
                'cancelada'
            )
        );
    }
}
