<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ColaboradorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\VeiculosClientesController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\NotasItemController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\MontadoraController;
use App\Http\Controllers\SetorServicoController;
use App\Http\Controllers\CategoriaFinanceiraController;
use App\Http\Controllers\ContaReceberController;
use App\Http\Controllers\FormaPagamentoController;
use App\Http\Controllers\RecebimentoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\AnexoController;

//use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('index');
});

Route::get('/home', function () {
    return view('index');
});

// Usuários estão desbloqueados.
Route::middleware(['auth', 'check.blocked'])->group(function () {

    Route::get('/perfil', function () {
        return view('cliente/editarcliente');
    })->name('perfil');

    Route::put('/perfil/{id_user}/update', [UserController::class, 'update']);
    Route::resource('veiculosclientes', VeiculosClientesController::class);
    Route::get('/veiculos/montadora/{id}', [VeiculoController::class, 'porMontadora']);

    // Rotas para administradores.
    Route::middleware(['admin'])->group(function () {
        Route::resource('ordemservicos', OrdemServicoController::class);
        Route::resource('notasitem', NotasItemController::class);
        Route::resource('users', UserController::class);
        Route::resource('clientes', ClienteController::class);
        Route::resource('produtos', ProdutoController::class);
        Route::resource('colaboradores', ColaboradorController::class);
        Route::resource('enderecos', EnderecoController::class);
        Route::get('/endereco/create/{id}', [EnderecoController::class, 'create']);
        Route::get('/notas/{id}/pdf', [NotaController::class, 'gerarpdf'])->name('notas.pdf');
        Route::resource('notas', NotaController::class);
        Route::resource('veiculos', VeiculoController::class);
        Route::resource('montadoras', MontadoraController::class);
        Route::resource('setor-servicos', SetorServicoController::class);

        Route::resource('formas-pagamento', FormaPagamentoController::class)
            ->parameters(['formas-pagamento' => 'formaPagamento']);

        Route::resource('categorias-financeiras', CategoriaFinanceiraController::class)
            ->parameters(['categorias-financeiras' => 'categoriaFinanceira']);

        Route::resource('contas-receber', ContaReceberController::class)
            ->parameters(['contas-receber' => 'contaReceber']);

        Route::get('contas-receber/{contaReceber}/recebimentos/create', [RecebimentoController::class, 'create'])->name('recebimentos.create');

        Route::post('recebimentos', [RecebimentoController::class, 'store'])->name('recebimentos.store');

        Route::post('compras/{compra}/estoque', [CompraController::class, 'registrarEstoque'])
            ->name('compras.estoque')
            ->middleware(['auth', 'check.blocked']);

        Route::resource('compras', CompraController::class)
            ->middleware(['auth', 'check.blocked']);

        Route::resource('fornecedores', FornecedorController::class)
            ->parameters(['fornecedores' => 'fornecedor'])
            ->middleware(['auth', 'check.blocked']);

        Route::post('compras/{compra}/anexos', [AnexoController::class, 'storeCompra'])
            ->name('compras.anexos.store');

        Route::get('anexos/{anexo}/download', [AnexoController::class, 'download'])
            ->name('anexos.download');

        Route::delete('anexos/{anexo}', [AnexoController::class, 'destroy'])
            ->name('anexos.destroy');

    });
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Rotas autenticadas do sistema
});

Route::get('/erro-autenticacao', function () {
    return view('errors.403');
})->name('erro-autenticacao');

Route::patch('/users/{id}/block', [UserController::class, 'toggleBlock'])->name('toggleBlock');

// Rotas de teste para as novas views:
Route::get('/termos-de-uso', function () {
    return view('termos/termosdeuso');
})->name('termos');
