<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\PecaController;
use App\Http\Controllers\ColaboradorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PecaVendaController;
//use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('index');
});

Route::get('/home', function () {
    return view('index');
});

// Usuários desbloqueados
Route::middleware(['auth', 'check.blocked'])->group(function () {
    //Rotas para administradores.
    Route::middleware(['admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::get('/cliente', [ClienteController::class, 'index'])->name('clientes.index');
        Route::resource('pecavendas', PecaVendaController::class);
        Route::resource('pecas', PecaController::class);
        Route::resource('colaboradors', ColaboradorController::class);
        Route::resource('enderecos', EnderecoController::class);
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