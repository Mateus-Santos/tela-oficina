<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\CadastrarClienteMarketingRequest;
use App\Mail\Marketing\ConfirmarCadastroCliente;
use App\Models\Cliente;
use App\Models\MarketingCadastroPendente;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CadastroClienteMarketingController extends Controller
{
    private function exigirHabilitacao(): void
    {
        abort_unless(config('marketing.cadastro_cliente_habilitado', false), 404);
    }

    public function create(): View
    {
        $this->exigirHabilitacao();

        return view('marketing.cadastro-cliente');
    }

    public function store(CadastrarClienteMarketingRequest $request): RedirectResponse
    {
        $this->exigirHabilitacao();
        $dados = $request->validated();

        // Resposta indistinguivel para e-mails ja cadastrados: nao expor contas.
        if (!User::query()->where('email', $dados['email'])->exists()) {
            $token = Str::random(64);

            $pendente = MarketingCadastroPendente::query()->updateOrCreate(
                ['email' => $dados['email']],
                [
                    'nome' => $dados['nome'],
                    'whatsapp' => $dados['whatsapp'],
                    'senha_hash' => Hash::make($dados['senha']),
                    'token_hash' => hash('sha256', $token),
                    'expira_em' => now()->addMinutes(30),
                    'consentido_em' => now(),
                ]
            );

            // Apenas a posse do e-mail confirma a conta; o token nao e salvo em claro.
            $link = URL::temporarySignedRoute(
                'marketing.cadastro.confirmar',
                now()->addMinutes(30),
                ['cadastro' => $pendente->id, 'token' => $token]
            );

            Mail::to($dados['email'])->send(new ConfirmarCadastroCliente($link));
        }

        return redirect()->route('marketing.cadastro.create')->with(
            'marketing_cadastro_enviado',
            'Se o endereço puder ser cadastrado, enviaremos um link de confirmação. '
            .'Se você já possui conta, use Entrar ou Recuperar senha.'
        );
    }

    public function confirm(int $cadastro, string $token): RedirectResponse
    {
        $this->exigirHabilitacao();

        if (!preg_match('/\A[A-Za-z0-9]{64}\z/D', $token)) {
            abort(403);
        }

        // Concorrencia: link valido somente uma vez, com travamento do registro.
        $ativado = DB::transaction(function () use ($cadastro, $token): bool {
            $pendente = MarketingCadastroPendente::query()
                ->whereKey($cadastro)->lockForUpdate()->first();

            if (!$pendente || $pendente->expira_em->isPast()
                || !hash_equals($pendente->token_hash, hash('sha256', $token))) {
                return false;
            }

            if (User::query()->where('email', $pendente->email)->exists()) {
                $pendente->delete();
                return false;
            }

            // Nao reutilizar pessoa ou cliente antigo com base apenas no telefone.
            $pessoa = Pessoa::query()->create([
                'nome' => $pendente->nome,
                'telefone_1' => $pendente->whatsapp,
            ]);

            $user = User::query()->create([
                'pessoa_id' => $pessoa->id,
                'email' => $pendente->email,
                'password' => $pendente->senha_hash,
                'perfil' => User::PERFIL_CLIENTE,
                'status' => true,
            ]);
            // email_verified_at nao pertence ao fillable do User atual.
            $user->forceFill(['email_verified_at' => now()])->save();

            Cliente::query()->create(['pessoa_id' => $pessoa->id, 'pontos' => 0]);
            $pendente->delete();

            return true;
        });

        abort_unless($ativado, 403);

        return redirect()->route('login')->with(
            'status', 'Conta confirmada. Entre com seu e-mail e senha.'
        );
    }
}
