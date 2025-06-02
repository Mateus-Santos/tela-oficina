<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ColaboradorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\produtoVendaController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\ManutencaoController;
use App\Http\Controllers\ContratoServicoController;
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
    Route::resource('veiculos', VeiculoController::class);
    Route::resource('contratoservico', ContratoServicoController::class);
    //Rotas para administradores.
    Route::middleware(['admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::get('/cliente', [ClienteController::class, 'index'])->name('clientes.index');
        Route::resource('produtovendas', produtoVendaController::class);
        Route::resource('produtos', ProdutoController::class);
        Route::resource('colaboradors', ColaboradorController::class);
        Route::resource('enderecos', EnderecoController::class);
        Route::resource('manutencoes', ManutencaoController::class);
        Route::get('/endereco/create/{id}', [EnderecoController::class, 'create']);
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
