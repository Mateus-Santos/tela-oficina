#!/usr/bin/env python3
"""FEUX L02C: modificacoes ancoradas por commit, nenhum match aproximado."""
from pathlib import Path
import sys
root=Path(sys.argv[1]).resolve()

def rewrite(path, func):
    p=root/path
    before=p.read_text()
    after=func(before)
    assert before!=after, 'Arquivo sem alteracoes: '+path
    p.write_text(after)
    print('PATCH_L02C='+path)

def only(content, old, new):
    assert content.count(old)==1, f'Ancora divergente ou repetida: {old[:80]!r}: {content.count(old)}'
    return content.replace(old,new,1)

def os_action(s):
    start=s.index('    public function execute(')
    end=s.index('    private function obterEtapaInicial()',start)
    new='''    /** Entrada publica: nao permite O.S. persistida sem Nota. */
    public function execute(array $dados, ?int $userId = null): OrdemServico
    {
        return app(\\App\\Services\\Nota\\CriarOsComNota::class)
            ->execute($dados, $userId)['os'];
    }

    /**
     * Operacao interna: apenas dentro da transacao da Nota ja criada.
     * O chamador deve inserir o NotasItem antes do commit externo.
     */
    public function criarParaNota(\\App\\Models\\Nota $nota, array $dados, ?int $userId = null): OrdemServico
    {
        if (DB::transactionLevel() < 1) {
            throw new \\LogicException('Criacao de O.S. vinculada exige transacao da Nota.');
        }
        $nota = \\App\\Models\\Nota::query()->lockForUpdate()->findOrFail($nota->id);
        if ($nota->status !== 'Aberto') {
            throw ValidationException::withMessages(['nota' => 'Somente Nota aberta recebe O.S.']);
        }
        if ((int) $nota->cliente_id !== (int) ($dados['cliente_id'] ?? 0)
            || (int) $nota->veiculo_cliente_id !== (int) ($dados['veiculo_cliente_id'] ?? 0)) {
            throw ValidationException::withMessages(['nota' => 'Cliente ou veiculo da O.S. divergente da Nota.']);
        }
        $veiculo = VeiculosCliente::query()
            ->whereKey($dados['veiculo_cliente_id'])
            ->whereHas('clientes', fn ($q) => $q->where('clientes.id', $dados['cliente_id']))
            ->first();
        if (! $veiculo) {
            throw ValidationException::withMessages([
                'veiculo_cliente_id' => 'O veiculo nao pertence ao cliente informado.',
            ]);
        }
        $etapa = $this->obterEtapaInicial();
        $ordemServico = OrdemServico::create([
            'nota_id' => $nota->id,
            'data_abertura' => now(),
            'cliente_id' => $dados['cliente_id'],
            'veiculo_cliente_id' => $dados['veiculo_cliente_id'],
            'setor_servico_id' => $dados['setor_servico_id'],
            'etapa_id' => $etapa->id,
            'descricao' => $dados['descricao'] ?? null,
            'valor' => $this->normalizarValor($dados['valor']),
            'status' => 'aberta',
        ]);
        OrdemServicoEtapaHistorico::create([
            'ordem_servico_id' => $ordemServico->id,
            'etapa_origem_id' => null,
            'etapa_destino_id' => $etapa->id,
            'etapa_origem_nome' => null,
            'etapa_destino_nome' => $etapa->nome,
            'user_id' => $userId,
            'motivo' => 'Etapa inicial definida na criacao da Ordem de Servico.',
        ]);
        return $ordemServico->fresh(['nota', 'setorServico', 'etapa', 'historicoEtapas']);
    }

'''
    return s[:start]+new+s[end:]
rewrite('app/Actions/OrdemServico/CriarOrdemServico.php',os_action)

def model_os(s):
    s=only(s,"        'cliente_id',\n        'data_abertura',", "        'cliente_id',\n        'nota_id',\n        'data_abertura',")
    s=only(s,"        'etapa_id' => 'integer',", "        'nota_id' => 'integer',\n        'etapa_id' => 'integer',")
    s=only(s,"    public function veiculosCliente(): BelongsTo", "    public function nota(): BelongsTo\n    {\n        return $this->belongsTo(Nota::class, 'nota_id');\n    }\n\n    public function veiculosCliente(): BelongsTo")
    return s
rewrite('app/Models/OrdemServico.php', model_os)

def rascunho(s):
    old="""        return app(CriarOrdemServico::class)->execute([
            'cliente_id' => $nota->cliente_id,"""
    new="""        return app(CriarOrdemServico::class)->criarParaNota($nota, [
            'cliente_id' => $nota->cliente_id,"""
    return only(s,old,new)
rewrite('app/Services/Nota/OsRascunhoNaNota.php', rascunho)

def nota_item(s):
    match='''    public function itemable()
    {'''
    replace='''    /**
     * Nao aceita inserir ou transferir item O.S. para Nota diferente
     * daquela registrada na FK canonica. Operacoes em massa continuam
     * protegidas nos Actions/Controllers; nao sao eventos Eloquent.
     */
    protected static function booted(): void
    {
        $verificar = static function (self $item): void {
            if ($item->itemable_type !== OrdemServico::class) {
                return;
            }
            $os = OrdemServico::query()->findOrFail((int) $item->itemable_id);
            if ((int) $os->nota_id !== (int) $item->nota_id) {
                throw \\Illuminate\\Validation\\ValidationException::withMessages([
                    'itens' => 'O.S. vinculada a Nota divergente. Operacao bloqueada.',
                ]);
            }
        };
        static::creating($verificar);
        static::updating($verificar);
    }

    public function itemable()
    {'''
    return only(s,match,replace)
rewrite('app/Models/NotasItem.php', nota_item)

# Fixture historica: O.S. publica agora ja possui Nota; editar a existente.
p='tests/Feature/Notas/FinalizarNotaTest.php'
def atualizar_fixture(s):
    anchor='private function criarNotaComOrdemServico('
    assert s.count(anchor)==1, 'Fixture de finalizacao de Nota nao encontrada'
    i=s.index(anchor)
    end=s.find('\n    private function ', i+len(anchor))
    if end<0: end=s.find('\n    public function ', i+len(anchor))
    assert end>i, 'Limite da fixture nao encontrado'
    trecho=s[i:end]
    old='''return app(
            CriarNota::class
        )->execute(['''
    new='''return app(
            \\App\\Actions\\Notas\\AtualizarNota::class
        )->execute(Nota::findOrFail($ordemServico->nota_id), ['''
    assert trecho.count(old)==1, 'Fixture nao utiliza CriarNota na forma esperada'
    return s[:i]+trecho.replace(old,new,1)+s[end:]
rewrite(p, atualizar_fixture)
