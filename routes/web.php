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

// Tela principal
Route::view('/', 'index');
Route::view('/home', 'index');

//Acesso a autenticação por conta google.
Route::get('login/google/redirect', [googleAuthController::class, 'redirect']);
Route::get('login/google/callback', [googleAuthController::class, 'callback']);

//User bloqueado
Route::patch('/users/{id}/block', [UserController::class, 'toggleBlock'])->name('toggleBlock');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

//Rotas para administradores.
Route::middleware(['admin'])->group(function () {
    Route::resource('pessoas', PessoaController::class);
    Route::resource('clientes', ClienteController::class);
    Route::resource('pecavendas', PecaVendaController::class);
    Route::resource('pecas', PecaController::class);
    Route::resource('colaboradors', ColaboradorController::class);
    Route::resource('enderecos', EnderecoController::class);
});