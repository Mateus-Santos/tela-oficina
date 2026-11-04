<?php

namespace App\Actions\FinanceiroSocios;

use App\Models\RetiradaSocio;
use App\Models\Socio;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarRetiradaSocio
{
    public function execute(Socio $socio, array $dados): RetiradaSocio
    {
        $valor = DinheiroSocios::centavos($dados['valor']);
        if ($valor <= 0) {
            throw new InvalidArgumentException('A retirada deve ser positiva.');
        }

        $tipo = $dados['tipo'];
        if (!in_array($tipo, ['retirada_livre', 'remuneracao'], true)) {
            throw new InvalidArgumentException('Tipo de retirada invalido.');
        }

        return DB::transaction(fn () => $socio->retiradas()->create([
            'tipo' => $tipo,
            'valor' => DinheiroSocios::decimal($valor),
            'data_retirada' => $dados['data_retirada'],
            'observacoes' => $dados['observacoes'] ?? null,
        ]));
    }
}
