#!/usr/bin/env python3
"""FEUX L02B. Alteracoes ancoradas: aborta se os arquivos mudaram."""
from pathlib import Path
import sys

root=Path(sys.argv[1]).resolve()

def replace(path, old, new):
    f=root/path
    content=f.read_text()
    count=content.count(old)
    if count != 1:
        raise SystemExit(f'BLOQUEADO: ancora nao e unica: {path}; ocorrencias={count}; prefixo={old[:110]!r}')
    f.write_text(content.replace(old,new,1))
    print('PATCH_OK='+str(path))

# Request contracts: draft IDs negativos para OS; nunca para produtos.
for fp in ['app/Http/Requests/Notas/StoreNotaRequest.php','app/Http/Requests/Notas/UpdateNotaRequest.php']:
    replace(fp,
"""            'itens.*.itemable_id' => [
                'required',
                'integer',
                'min:1',
            ],""",
"""            'itens.*.itemable_id' => [
                'required',
                'integer',
                'not_in:0',
            ],

            'itens.*.os_nova_setor_servico_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:setor_servicos,id',
            ],""")
    replace(fp,
"""                $this->validarDescontos(
                    $validator
                );""",
"""                $this->validarDescontos(
                    $validator
                );

                $this->validarRascunhosOs($validator);""")
    replace(fp, '    private function normalizarNumero(\n',
"""    private function validarRascunhosOs(Validator $validator): void
    {
        foreach ((array) $this->input('itens', []) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (int) ($item['itemable_id'] ?? 0);
            $temSetor = array_key_exists('os_nova_setor_servico_id', $item);
            if ($id < 0) {
                if (($item['itemable_type'] ?? null) !== OrdemServico::class
                    || !$temSetor || !$item['os_nova_setor_servico_id']
                    || (int) ($item['quantidade'] ?? 0) !== 1
                    || !$this->filled('cliente_id')
                    || !$this->filled('veiculo_cliente_id')) {
                    $validator->errors()->add(
                        "itens.{$index}.itemable_id",
                        'O.S. nova exige cliente, veiculo, setor e quantidade 1.'
                    );
                }
            } elseif ($temSetor) {
                $validator->errors()->add(
                    "itens.{$index}.itemable_id",
                    'Dados de O.S. nova nao podem acompanhar item existente.'
                );
            }
        }
    }

    private function normalizarNumero(
""")

# Criar Nota: valida rascunho ANTES de salvar Nota. Salva O.S. e item na MESMA transacao.
p='app/Actions/Notas/CriarNota.php'
replace(p,
"""            if ($tipo === OrdemServico::class) {
                $this->validarOrdemServico(
                    $itemId,
                    $index,
                    $clienteId,
                    $veiculoClienteId
                );
            }""",
"""            if ($tipo === OrdemServico::class) {
                if ($itemId < 0) {
                    \\App\\Services\\Nota\\OsRascunhoNaNota::validar(
                        $dadosItem, $clienteId, $veiculoClienteId,
                        "itens.{$index}.itemable_id"
                    );
                } else {
                    $this->validarOrdemServico(
                        $itemId, $index, $clienteId, $veiculoClienteId
                    );
                }
            }""")
replace(p,
"""        return NotasItem::create(
            $dadosItemNota
        );""",
"""        if ($dadosItem['itemable_type'] === OrdemServico::class
            && (int) $dadosItem['itemable_id'] < 0) {
            $novaOs = \\App\\Services\\Nota\\OsRascunhoNaNota::criar(
                $nota, $dadosItem, auth()->id()
            );
            $dadosItemNota['itemable_id'] = $novaOs->id;
        }

        return NotasItem::create(
            $dadosItemNota
        );""")

# Editar Nota: IDs OS antigos nao podem ser alterados ou removidos sem procedimento dedicado.
p='app/Actions/Notas/AtualizarNota.php'
replace(p,
"""                if (!empty($idsEnviados)) {
                    $nota""",
"""                $osRemovidas = $nota->itens()
                    ->where('itemable_type', OrdemServico::class)
                    ->when(!empty($idsEnviados),
                        fn ($q) => $q->whereNotIn('id', $idsEnviados))
                    ->exists();
                if ($osRemovidas) {
                    throw ValidationException::withMessages([
                        'itens' => 'Nao e permitido remover O.S. vinculada da Nota.'
                    ]);
                }

                if (!empty($idsEnviados)) {
                    $nota""")
replace(p,
"""                        ->exists();

                if (!$itemPertence) {""",
"""                        ->first();

                if (!$itemPertence) {""")
replace(p,
"""                if (!$itemPertence) {
                    throw ValidationException::withMessages([
                        "itens.{$index}.id" =>
                            'Um dos itens enviados não pertence a esta Nota.',
                    ]);
                }
            }""",
"""                if (!$itemPertence) {
                    throw ValidationException::withMessages([
                        "itens.{$index}.id" =>
                            'Um dos itens enviados não pertence a esta Nota.',
                    ]);
                }
                if ($itemPertence->itemable_type === OrdemServico::class
                    && ($dadosItem['itemable_type'] !== OrdemServico::class
                        || (int) $dadosItem['itemable_id'] !== (int) $itemPertence->itemable_id)) {
                    throw ValidationException::withMessages([
                        "itens.{$index}.id" => 'Nao e permitido trocar o vinculo da O.S. nesta Nota.',
                    ]);
                }
            }""")
replace(p,
"""            if (
                $tipo
                === OrdemServico::class
            ) {
                $this->validarOrdemServico(
                    $nota,
                    $itemId,
                    $index,
                    $clienteId,
                    $veiculoClienteId
                );
            }""",
"""            if (
                $tipo
                === OrdemServico::class
            ) {
                if ($itemId < 0) {
                    \\App\\Services\\Nota\\OsRascunhoNaNota::validar(
                        $dadosItem, $clienteId, $veiculoClienteId,
                        "itens.{$index}.itemable_id"
                    );
                } else {
                    $this->validarOrdemServico(
                        $nota, $itemId, $index, $clienteId, $veiculoClienteId
                    );
                }
            }""")
replace(p,
"""        $dadosParaSalvar = [
""",
"""        if ($dadosItem['itemable_type'] === OrdemServico::class
            && (int) $dadosItem['itemable_id'] < 0) {
            $novaOs = \\App\\Services\\Nota\\OsRascunhoNaNota::criar(
                $nota, $dadosItem, auth()->id()
            );
            $dadosItem['itemable_id'] = $novaOs->id;
        }

        $dadosParaSalvar = [
""")

# Proibir exclusao avulsa de item O.S. (inclusive sob condicoes de corrida).
p='app/Http/Controllers/NotasItemController.php'
replace(p,
"""            $itemTransacional->delete();""",
"""            if ($itemTransacional->itemable_type === \\App\\Models\\OrdemServico::class) {
                throw ValidationException::withMessages([
                    'nota' => 'A O.S. vinculada nao pode ser removida isoladamente.',
                ]);
            }
            $itemTransacional->delete();""")

# Cadastro direto de OS: exige Nota criada na mesma transacao.
p='app/Http/Controllers/OrdemServicoController.php'
replace(p,
"""        $criarOrdemServico->execute(
            $dados,
            $request->user()->id
        );

        return redirect()
            ->route('ordemservicos.index')
            ->with(
                'success',
                'Ordem de serviço cadastrada com sucesso!'
            );""",
"""        $resultado = app(\\App\\Services\\Nota\\CriarOsComNota::class)
            ->execute($dados, $request->user()->id);

        return redirect()
            ->route('notas.show', $resultado['nota']->id)
            ->with('success', 'O.S. criada e vinculada a Nota #' . $resultado['nota']->id . '.');""")

# Endpoint legado de OS rapida tambem so cria se puder criar Nota inteira.
p='app/Http/Controllers/Api/NotaOrdemServicoRapidaController.php'
replace(p,
"""        $ordemServico =
            $criarOrdemServico->execute(
                $dados,
                $request->user()->id
            );""",
"""        $resultado = app(\\App\\Services\\Nota\\CriarOsComNota::class)
            ->execute($dados, $request->user()->id);
        $ordemServico = $resultado['os'];""")
replace(p,
"""                'disponivel' => true,

                'nota' => null,""",
"""                'disponivel' => false,

                'nota' => ['id' => $resultado['nota']->id],
                'nota_id' => $resultado['nota']->id,""")

# Exibir a OS ainda nao persistida como rascunho, nao como ID negativo.
p='resources/js/notaitem/itens.js'
replace(p,
"""                            <div class="fw-semibold mt-2">
                                #${escaparHtml(itemId)}
                            </div>

                            <div class="small text-muted mt-1">
                                Serviço vinculado à Nota
                            </div>""",
"""                            <div class="fw-semibold mt-2">
                                ${Number(itemId) < 0 ? 'O.S. em rascunho' : '#' + escaparHtml(itemId)}
                            </div>

                            <div class="small text-muted mt-1">
                                ${Number(itemId) < 0
                                    ? 'Sera criada quando salvar a Nota'
                                    : 'Serviço vinculado à Nota'}
                            </div>""")

# Draft em browser; sem API antes de salvar Nota. O indicador de loading
# so aparece durante operacoes remotas reais (salvar Nota / mover Kanban).
p='resources/js/notaitem/os-rapida.js'
f=root/p
c=f.read_text()
a='        async function salvar() {'
b='        tipoInput.addEventListener('
if c.count(a)!=1 or c.count(b)!=1 or c.index(a)>=c.index(b):
    raise SystemExit('BLOQUEADO: funcoes de OS rapida alteradas')
new="""        function salvar() {
            if (salvando) return;
            esconderAlerta();
            const cliente = clienteId();
            const veiculo = veiculoId();
            const setor = setorInput?.value || '';
            const descricao = descricaoInput?.value?.trim() || '';
            const valorTexto = String(valorInput?.value || '');
            const valor = Number(valorTexto.includes(',')
                ? valorTexto.replace(/\\./g, '').replace(',', '.')
                : valorTexto);
            if (!cliente || !veiculo || !setor || !descricao
                || !Number.isFinite(valor) || valor < 0) {
                mostrarAlerta('Informe cliente, veiculo, setor, descricao e valor valido.');
                return;
            }
            salvando = true;
            salvarBotao.disabled = true;
            try {
                // ID temporario negativo: NENHUMA O.S. e gravada antes da Nota.
                const id = -(Date.now() + Math.floor(Math.random() * 1000));
                preencherBuilder({ id, descricao, valor });
                document.getElementById('btn-adicionar-item')?.click();
                const linha = Array.from(document.querySelectorAll(
                    '#container-itens-dinamicos tr[data-item-index]'
                )).find((tr) => tr.dataset.itemableId === String(id));
                if (!linha) {
                    throw new Error('Nao foi possivel adicionar o rascunho a Nota.');
                }
                const campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = `itens[${linha.dataset.itemIndex}][os_nova_setor_servico_id]`;
                campo.value = setor;
                linha.querySelector('td')?.appendChild(campo);
                limpar();
                fechar();
                // O backend persiste O.S. e item somente na transacao da Nota.
            } catch (error) {
                mostrarAlerta(error.message || 'Nao foi possivel adicionar O.S.');
            } finally {
                salvando = false;
                salvarBotao.disabled = false;
            }
        }

"""
c=c[:c.index(a)] + new + c[c.index(b):]
f.write_text(c)
print('PATCH_OK='+str(p))
