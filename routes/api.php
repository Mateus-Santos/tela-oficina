<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MontadoraController;
use App\Http\Controllers\Api\VeiculosClientesController;
use App\Models\Veiculo;

Route::get('/montadora/{id}/veiculos', function($id) {
    return Veiculo::with('montadora')
        ->where('montadora_id', $id)
        ->orderBy('nome')
        ->get();
});

Route::get('/api/clientes/{id}/veiculos', [VeiculosClientesController::class, 'veiculosPorCliente']);
Route::get('/veiculo/placa/{placa}', [VeiculosClientesController::class, 'buscarPorPlaca']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
