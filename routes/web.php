<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PecaController;
use App\Http\Controllers\ColaboradorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PecaVendaController;
use App\Http\Controllers\googleAuthController;
//use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('index');
});

Route::get('/home', function () {
    return view('index');
});


// Rotas Endereços.
Route::get('/enderecos', [EnderecoController::class, 'index'])->name('enderecos.index');
Route::get('/enderecos/create/{id}', [EnderecoController::class, 'create'])->name('enderecos.create');
Route::post('/enderecos', [EnderecoController::class, 'store'])->name('enderecos.store');
Route::post('/enderecos/{id}', [EnderecoController::class, 'show'])->name('enderecos.show');
Route::get('/enderecos/edit/{id}', [EnderecoController::class, 'edit'])->name('enderecos.edit');
Route::put('/enderecos/update/{id}', [EnderecoController::class, 'update'])->name('enderecos.update');
Route::delete('/enderecos/{id}', [EnderecoController::class, 'destroy'])->name('enderecos.destroy');

//Rotas Colaborador.
Route::get('/colaborador', [ColaboradorController::class, 'index'])->name('colaboradors.index');
Route::get('/colaboradors/create', [ColaboradorController::class, 'create'])->name('colaboradors.create');
Route::post('/colaboradors', [ColaboradorController::class, 'store'])->name('colaboradors.store');
Route::post('/colaboradors/{id}', [ColaboradorController::class, 'show'])->name('colaboradors.show');
Route::get('/colaboradors/edit/{id}', [ColaboradorController::class, 'edit'])->name('colaboradors.edit');
Route::put('/colaboradors/update/{id}', [ColaboradorController::class, 'update'])->name('colaboradors.update');
Route::delete('/colaboradors/{id}', [ColaboradorController::class, 'destroy'])->name('colaboradors.destroy');

//Rotas peca.
Route::get('/peca', [PecaController::class, 'index'])->name('pecas.index');
Route::get('/pecas/create', [PecaController::class, 'create'])->name('pecas.create');
Route::post('/pecas', [PecaController::class, 'store'])->name('pecas.store');
Route::post('/pecas/{id}', [PecaController::class, 'show'])->name('pecas.show');
Route::get('/pecas/edit/{id}', [PecaController::class, 'edit'])->name('pecas.edit');
Route::put('/pecas/update/{id}', [PecaController::class, 'update'])->name('pecas.update');
Route::delete('/pecas/{id}', [PecaController::class, 'destroy'])->name('pecas.destroy');

//Rotas vendas.
Route::get('/pecavenda', [PecaVendaController::class, 'index'])->name('pecavendas.index');
Route::get('/pecavendas/create', [PecaVendaController::class, 'create'])->name('pecavendas.create');
Route::post('/pecavendas', [PecaVendaController::class, 'store'])->name('pecavendas.store');
Route::post('/pecavendas/{id}', [PecaVendaController::class, 'show'])->name('pecavendas.show');
Route::get('/pecavendas/edit/{id}', [PecaVendaController::class, 'edit'])->name('pecavendas.edit');
Route::put('/pecavendas/update/{id}', [PecaVendaController::class, 'update'])->name('pecavendas.update');
Route::delete('/pecavendas/{id}', [PecaVendaController::class, 'destroy'])->name('pecavendas.destroy');


//Rotas Clientes.
Route::get('/cliente', [ClienteController::class, 'index'])->name('clientes.index');
Route::get('/clientes/create', [ClienteController::class, 'create'])->name('clientes.create');
Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
Route::post('/clientes/{id}', [ClienteController::class, 'show'])->name('clientes.show');
Route::get('/clientes/edit/{id}', [ClienteController::class, 'edit'])->name('clientes.edit');
Route::put('/clientes/update/{id}', [ClienteController::class, 'update'])->name('clientes.update');
Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');

Route::middleware(['admin'])->group(function () {
    Route::resource('pessoas', PessoaController::class);
});

Route::get('login/google/redirect', [googleAuthController::class, 'redirect']);
Route::get('login/google/callback', [googleAuthController::class, 'callback']);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'check.blocked'])->group(function () {
    // Suas rotas protegidas
});

Route::patch('/users/{id}/block', [UserController::class, 'toggleBlock'])->name('toggleBlock');