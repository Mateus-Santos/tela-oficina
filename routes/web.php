<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PecaController;
use App\Http\Controllers\ColaboradorController;
//use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('index');
});

Route::get('/home', function () {
    return view('index');
});

//Rotas Pessoas.
Route::get('/pessoas', [PessoaController::class, 'index'])->name('pessoas.index');
Route::get('/pessoas/create', [PessoaController::class, 'create'])->name('pessoas.create');
Route::post('/pessoas', [PessoaController::class, 'store'])->name('pessoas.store');
Route::post('/pessoas/{id}', [PessoaController::class, 'show'])->name('pessoas.show');
Route::get('/pessoas/edit/{id}', [PessoaController::class, 'edit'])->name('pessoas.edit');
Route::put('/pessoas/update/{id}', [PessoaController::class, 'update'])->name('pessoas.update');
Route::delete('/pessoas/{id}', [PessoaController::class, 'destroy'])->name('pessoas.destroy');

// Rotas Endereços.
Route::get('/enderecos', [EnderecoController::class, 'index'])->name('enderecos.index');
Route::get('/enderecos/create', [EnderecoController::class, 'create'])->name('enderecos.create');
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