<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\SolicitarOrcamentoPecaRequest;
use App\Models\MarketingLead;
use App\Models\Produto;
use App\Services\Marketing\AtribuicaoCampanha;
use App\Services\Marketing\PrivacidadeLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrcamentoPecasController extends Controller
{
    public function store(SolicitarOrcamentoPecaRequest $request): RedirectResponse
    {
        $dados = app(AtribuicaoCampanha::class)->paraEnvio($request, $request->validated());

        // A referência e a descrição do produto são sempre resolvidas
        // no servidor; preços, estoque e descrições enviados pelo visitante
        // nunca são aceitos como fonte confiável.
        $selecionados = $dados['itens'] ?? [];
        $ids = array_column($selecionados, 'produto_id');
        $produtos = Produto::query()
            ->whereIn('id', $ids)
            ->where('status', true)
            ->where('quantidade', '>', 0)
            ->get(['id', 'nome', 'codigo_fabricante'])
            ->keyBy('id');

        if ($produtos->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'itens' => 'Uma das peças selecionadas não está mais disponível. Revise a seleção.',
            ]);
        }

        $peca = trim((string) ($dados['peca'] ?? ''));
        $itens = [];

        if ($peca !== '') {
            $itens[] = ['produto_id' => null, 'descricao' => $peca, 'quantidade' => 1];
        }
        foreach ($selecionados as $selecionado) {
            $produto = $produtos->get($selecionado['produto_id']);
            $itens[] = [
                'produto_id' => $produto->id,
                'descricao' => mb_substr($produto->nome, 0, 180),
                'quantidade' => (int) $selecionado['quantidade'],
            ];
        }

        if ($itens === [] || count($itens) > 10) {
            throw ValidationException::withMessages([
                'itens' => 'Informe de uma a dez peças por solicitação.',
            ]);
        }
        $resumoPeca = $peca !== '' ? $peca : $itens[0]['descricao'];

        $ipCriptografado = app(PrivacidadeLead::class)->ipCriptografado($request);

        // A transação protege o protocolo e seus itens de gravação parcial.
        // Não gera nota, cliente, estoque ou conta a receber.
        $lead = DB::transaction(function () use ($dados, $resumoPeca, $itens, $ipCriptografado): MarketingLead {
            $lead = MarketingLead::create([
                'protocolo' => 'MKT-'.strtoupper(Str::random(12)),
                'nome' => $dados['nome'],
                'whatsapp' => $dados['whatsapp'],
                'peca' => $resumoPeca,
                'veiculo' => $dados['veiculo'] ?? null,
                'ano' => $dados['ano'] ?? null,
                'motor' => $dados['motor'] ?? null,
                'detalhes' => $dados['detalhes'] ?? null,
                'cidade' => $dados['cidade'] ?? null,
                'uf' => $dados['uf'] ?? null,
                'utm_source' => $dados['utm_source'] ?? null,
                'utm_medium' => $dados['utm_medium'] ?? null,
                'utm_campaign' => $dados['utm_campaign'] ?? null,
                'utm_content' => $dados['utm_content'] ?? null,
                'utm_term' => $dados['utm_term'] ?? null,
                'gclid' => $dados['gclid'] ?? null,
                'consentido_em' => now(),
                'status' => 'novo',
            ]);

            // A coluna nao e mass-assignable. Mesmo quando habilitada, a
            // coleta e cifrada e restrita ao servidor.
            if ($ipCriptografado !== null) {
                $lead->forceFill(['ip_criptografado' => $ipCriptografado])->save();
            }

            foreach ($itens as $indice => $item) {
                $lead->itens()->create($item + ['ordem' => $indice + 1]);
            }

            return $lead;
        });

        // O item guarda o ID operacional; a referência enviada à equipe vem
        // exclusivamente do cadastro consultado no servidor, nunca do navegador.
        $listaItens = implode('; ', array_map(
            static function (array $item) use ($produtos): string {
                $referencia = $item['produto_id'] !== null
                    ? trim((string) ($produtos->get($item['produto_id'])?->codigo_fabricante ?? ''))
                    : '';

                return $item['quantidade'].'x '.$item['descricao']
                    .($referencia !== '' ? ' (Ref. fabricante: '.$referencia.')' : '');
            },
            $itens
        ));
        $mensagem = implode("\n", array_filter([
            'Olá! Solicitei um orçamento pelo site da SOS Mecânica.',
            'Protocolo: '.$lead->protocolo,
            'Nome: '.$lead->nome,
            'Peças: '.Str::limit($listaItens, 750),
            'Veículo: '.$lead->veiculo,
            'Ano: '.$lead->ano,
            'Motor: '.$lead->motor,
            'Detalhes: '.$lead->detalhes,
        ], fn (string $linha): bool => !str_ends_with($linha, ': ')));

        $whatsapp = preg_replace('/\D+/', '', (string) config('oficina.whatsapp'));
        $link = 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($mensagem);

        return redirect()
            ->route('marketing.pecas.orcamento')
            ->with('marketing_protocolo', $lead->protocolo)
            ->with('marketing_whatsapp', $link);
    }
}
