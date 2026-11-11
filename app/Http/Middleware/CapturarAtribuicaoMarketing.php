<?php

namespace App\Http\Middleware;

use App\Services\Marketing\AtribuicaoCampanha;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CapturarAtribuicaoMarketing
{
    public function __construct(private readonly AtribuicaoCampanha $atribuicao) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->atribuicao->capturar($request);

        return $next($request);
    }
}
