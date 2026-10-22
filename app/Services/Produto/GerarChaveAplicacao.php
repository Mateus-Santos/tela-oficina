<?php

namespace App\Services\Produto;

use Illuminate\Support\Str;

class GerarChaveAplicacao
{
    public function gerar(
        ?int $veiculoId,
        array $dados = []
    ): string {
        $campos = [
            'veiculo_id' =>
                $veiculoId,

            'mes_ano' =>
                $this->normalizar(
                    $dados['mes_ano']
                    ?? null
                ),

            'modelo' =>
                $this->normalizar(
                    $dados['modelo']
                    ?? null
                ),

            'motor' =>
                $this->normalizar(
                    $dados['motor']
                    ?? null
                ),

            'comp' =>
                $this->normalizar(
                    $dados['comp']
                    ?? null
                ),

            'valvulas' =>
                $this->normalizar(
                    $dados['valvulas']
                    ?? null
                ),

            'cilindros' =>
                $this->normalizar(
                    $dados['cilindros']
                    ?? null
                ),

            'combustivel' =>
                $this->normalizar(
                    $dados['combustivel']
                    ?? null
                ),

            'complemento' =>
                $this->normalizar(
                    $dados['complemento']
                    ?? null
                ),
        ];

        return hash(
            'sha256',
            json_encode(
                $campos,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            )
        );
    }

    private function normalizar(
        mixed $valor
    ): ?string {
        if ($valor === null) {
            return null;
        }

        $valor = Str::of(
            (string) $valor
        )
            ->ascii()
            ->upper()
            ->squish()
            ->value();

        return $valor !== ''
            ? $valor
            : null;
    }
}
