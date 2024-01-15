<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\UserController;

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

//Rotas Pessoas.
Route::get('/pessoas', [PessoaController::class, 'index'])->name('pessoas.index');
Route::get('/pessoas/create', [PessoaController::class, 'create'])->name('pessoas.create');
Route::post('/pessoas', [PessoaController::class, 'store'])->name('pessoas.store');
Route::post('/pessoas/{pessoa}', [PessoaController::class, 'show'])->name('pessoas.show');
Route::post('/pessoas/{pessoa}/edit', [PessoaController::class, 'edit'])->name('pessoas.edit');
Route::put('/pessoas/{pessoa}', [PessoaController::class, 'update'])->name('pessoas.update');
Route::delete('/pessoas/{pessoa}', [PessoaController::class, 'destroy'])->name('pessoas.destroy');

// Rotas Endereços.
Route::get('/enderecos', [EnderecoController::class, 'index'])->name('enderecos.index');
Route::get('/enderecos/create', [EnderecoController::class, 'create'])->name('enderecos.create');
Route::post('/enderecos', [EnderecoController::class, 'store'])->name('enderecos.store');
Route::post('/enderecos/{endereco}', [EnderecoController::class, 'show'])->name('enderecos.show');
Route::post('/enderecos/{endereco}/edit', [EnderecoController::class, 'edit'])->name('enderecos.edit');
Route::put('/enderecos/{endereco}', [EnderecoController::class, 'update'])->name('enderecos.update');
Route::delete('/enderecos/{endereco}', [EnderecoController::class, 'destroy'])->name('enderecos.destroy');