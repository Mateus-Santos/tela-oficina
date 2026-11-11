<?php

namespace App\Services\Marketing;

use Illuminate\Http\Request;

/** Guarda somente os parametros comerciais presentes na URL de entrada. */
final class AtribuicaoCampanha
{
    private const CHAVE_SESSAO = 'marketing.atribuicao';

    private const LIMITES = [
        'utm_source' => 150,
        'utm_medium' => 150,
        'utm_campaign' => 150,
        'utm_content' => 150,
        'utm_term' => 150,
        'gclid' => 200,
    ];

    public function capturar(Request $request): void
    {
        $entrada = $this->filtrar($request->query());

        // Uma visita direta nao apaga a campanha anterior. Uma URL rastreada
        // inicia uma nova atribuicao, sem misturar valores da campanha antiga.
        if ($entrada !== []) {
            $request->session()->put(self::CHAVE_SESSAO, $entrada);
        }
    }

    public function registrada(Request $request): array
    {
        $dados = $request->session()->get(self::CHAVE_SESSAO, []);

        return is_array($dados) ? $this->filtrar($dados) : [];
    }

    public function paraEnvio(Request $request, array $dados): array
    {
        $registrada = $this->registrada($request);

        if ($registrada === []) {
            // Compatibilidade com integracoes e formularios anteriores:
            // sem visita rastreada na sessao, mantem os campos validados.
            return $dados;
        }

        // Campos ocultos do navegador nao substituem a origem ja capturada.
        foreach (self::LIMITES as $campo => $limite) {
            $dados[$campo] = $registrada[$campo] ?? null;
        }

        return $dados;
    }

    private function filtrar(array $entrada): array
    {
        $validos = [];

        foreach (self::LIMITES as $campo => $limite) {
            $valor = $entrada[$campo] ?? null;
            if (!is_string($valor)) {
                continue;
            }

            $valor = trim($valor);
            if ($valor === '' || !mb_check_encoding($valor, 'UTF-8')
                || mb_strlen($valor) > $limite || preg_match('/[\x00-\x1f\x7f]/', $valor)) {
                continue;
            }

            $validos[$campo] = $valor;
        }

        return $validos;
    }
}
