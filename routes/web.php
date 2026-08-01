<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ColaboradorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProdutoVendaController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\VeiculosClientesController;
use App\Http\Controllers\ManutencaoController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\NotasItemController;
use App\Http\Controllers\NotaController;
//use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('index');
});

Route::get('/home', function () {
    return view('index');
});

//Usuários está desbloqueados.
Route::middleware(['auth', 'check.blocked'])->group(function () {
    Route::get('/perfil', function () {
        return view('cliente/editarcliente');
    })->name('perfil');
    Route::put('/perfil/{id_user}/update', [UserController::class, 'update']);
    Route::resource('veiculosclientes', VeiculosClientesController::class);
    Route::get('/veiculos/montadora/{id}', [VeiculoController::class, 'porMontadora']);
    //Rotas para administradores.
    Route::middleware(['admin'])->group(function () {
        Route::resource('ordemservicos', OrdemServicoController::class);
        Route::resource('notasitem', NotasItemController::class);
        Route::resource('users', UserController::class);
        Route::resource('clientes', ClienteController::class);
        Route::resource('produtovendas', ProdutoVendaController::class);
        Route::resource('produtos', ProdutoController::class);
        Route::resource('colaboradores', ColaboradorController::class);
        Route::resource('enderecos', EnderecoController::class);
        Route::get('/endereco/create/{id}', [EnderecoController::class, 'create']);
        Route::get('/notas/{id}/pdf', [NotaController::class, 'gerarpdf'])->name('notas.pdf');
        Route::resource('notas', NotaController::class);
    });
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});


Route::get('/erro-autenticacao', function () {
    return view('errors.403');
})->name('erro-autenticacao');

Route::patch('/users/{id}/block', [UserController::class, 'toggleBlock'])->name('toggleBlock');

// Rotas de teste para as novas views:

Route::get('/termos-de-uso', function () {
    return view('termos/termosdeuso');
})->name('termos');
