<?php

namespace App\Actions\Produto;

use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AtualizarProduto
{
    public function execute(
        Produto $produto,
        array $dados
    ): Produto {
        $novasImagens = [];

        foreach ($dados['imagens'] ?? [] as $imagem) {
            if (!$imagem instanceof UploadedFile) {
                continue;
            }

            $novasImagens[] = $imagem
                ->store('produtos', 'public');
        }

        try {
            $produto = DB::transaction(
                function () use (
                    $produto,
                    $dados,
                    $novasImagens
                ) {
                    $marca = Marca::query()
                        ->findOrFail($dados['marca_id']);

                    $produto->update([
                        'nome' => $dados['nome'],
                        'marca_id' => $marca->id,
                        'descricao' => $dados['descricao'],
                        'preco_uni' => $dados['preco_uni'],
                        'quantidade' => $dados['quantidade'],
                        'estoque_minimo' => $dados['estoque_minimo'] ?? 0,

                        'status' => array_key_exists('status', $dados)
                            ? (bool) $dados['status']
                            : $produto->status,

                        'codigo_fabricante' => $dados['codigo_fabricante'],
                        'codigo_barras' => $dados['codigo_barras'] ?? null,

                        'ncm' => $dados['ncm'] ?? null,
                        'cest' => $dados['cest'] ?? null,
                        'ex_tipi' => $dados['ex_tipi'] ?? null,
                        'origem_mercadoria' => $dados['origem_mercadoria'] ?? null,
                        'unidade_comercial' => $dados['unidade_comercial'] ?? null,
                        'unidade_tributavel' => $dados['unidade_tributavel'] ?? null,
                        'fator_conversao' => $dados['fator_conversao'] ?? null,
                        'peso_liquido' => $dados['peso_liquido'] ?? null,
                        'peso_bruto' => $dados['peso_bruto'] ?? null,
                    ]);

                    $existentes = $produto
                        ->imagens()
                        ->get()
                        ->keyBy('id');

                    $tokens = json_decode(
                        $dados['ordem_imagens'] ?? '[]',
                        true
                    );

                    if (!is_array($tokens)) {
                        $tokens = [];
                    }

                    $itensOrdenados = [];
                    $existentesUtilizadas = [];
                    $novasUtilizadas = [];

                    foreach ($tokens as $token) {
                        if (!is_string($token)) {
                            continue;
                        }

                        if (str_starts_with($token, 'existente:')) {
                            $id = (int) substr(
                                $token,
                                strlen('existente:')
                            );

                            if (!$existentes->has($id)) {
                                continue;
                            }

                            if (isset($existentesUtilizadas[$id])) {
                                continue;
                            }

                            $itensOrdenados[] = [
                                'tipo' => 'existente',
                                'id' => $id,
                            ];

                            $existentesUtilizadas[$id] = true;

                            continue;
                        }

                        if (str_starts_with($token, 'nova:')) {
                            $indice = (int) substr(
                                $token,
                                strlen('nova:')
                            );

                            if (!array_key_exists(
                                $indice,
                                $novasImagens
                            )) {
                                continue;
                            }

                            if (isset($novasUtilizadas[$indice])) {
                                continue;
                            }

                            $itensOrdenados[] = [
                                'tipo' => 'nova',
                                'indice' => $indice,
                            ];

                            $novasUtilizadas[$indice] = true;
                        }
                    }

                    foreach ($existentes as $imagem) {
                        if (isset($existentesUtilizadas[$imagem->id])) {
                            continue;
                        }

                        $itensOrdenados[] = [
                            'tipo' => 'existente',
                            'id' => $imagem->id,
                        ];
                    }

                    foreach ($novasImagens as $indice => $caminho) {
                        if (isset($novasUtilizadas[$indice])) {
                            continue;
                        }

                        $itensOrdenados[] = [
                            'tipo' => 'nova',
                            'indice' => $indice,
                        ];
                    }

                    foreach ($itensOrdenados as $ordem => $item) {
                        if ($item['tipo'] === 'existente') {
                            $existentes[$item['id']]
                                ->update([
                                    'ordem' => $ordem,
                                ]);

                            continue;
                        }

                        $produto->imagens()->create([
                            'caminho' => $novasImagens[
                                $item['indice']
                            ],
                            'ordem' => $ordem,
                        ]);
                    }

                    $produto->veiculos()->sync(
                        $dados['veiculos']
                    );

                    return $produto->fresh([
                        'marcaRelacionada',
                        'imagens',
                        'veiculos',
                    ]);
                }
            );
        } catch (\Throwable $e) {
            foreach ($novasImagens as $imagem) {
                Storage::disk('public')
                    ->delete($imagem);
            }

            throw $e;
        }

        return $produto;
    }
}
