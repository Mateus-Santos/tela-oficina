import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const base = '2f1ccbc4dd38c6b6dd72237989f82ea015625825';
const views = [
  { type: 'nota', path: 'resources/views/livewire/nota/seletor-cliente-veiculo.blade.php' },
  { type: 'os', path: 'resources/views/livewire/ordem-servico/seletor-cliente-veiculo.blade.php' },
];
const current = new Map(views.map(({ type, path }) => [type, readFileSync(path, 'utf8')]));
const original = new Map(views.map(({ type, path }) => [type, execFileSync('git', ['show', `${base}:${path}`], { encoding: 'utf8' })]));
const matches = (text, pattern) => [...text.matchAll(pattern)].map(x => x[0]);

for (const { type } of views) {
  test(`FE-06 ${type}: preserva TODOS os atributos wire e campos ocultos`, () => {
    const a = original.get(type), b = current.get(type);
    assert.deepEqual(matches(b, /wire:[\w.-]+(?:="[^"]*")?/g), matches(a, /wire:[\w.-]+(?:="[^"]*")?/g));
    assert.deepEqual(matches(b, /<input\s+[^>]*type="hidden"[^>]*>/gs), matches(a, /<input\s+[^>]*type="hidden"[^>]*>/gs));
  });
  test(`FE-06 ${type}: busca sem alterar IDs nem debounces`, () => {
    const s = current.get(type);
    for (const target of ['cliente', 'veiculo']) {
      const id = `busca_${target}_${type}`;
      assert.match(s, new RegExp(`type="search"\\s+id="${id}"`));
    }
    assert.equal(matches(s, /wire:model.live.debounce.300ms="buscaCliente"/g).length, 1);
    assert.equal(matches(s, /wire:model.live.debounce.300ms="buscaVeiculo"/g).length, 1);
    assert.equal(matches(s, /role="status"\s+aria-live="polite"\s+wire:loading/g).length, 2);
    assert.equal(matches(s, /class="small fw-semibold mt-2" role="status" aria-live="polite"/g).length, 2);
  });
  test(`FE-06 ${type}: selecionados, resultados e navegacao acessiveis`, () => {
    const s = current.get(type);
    for (const label of ['cliente', 'veículo']) {
      assert.ok(s.includes(`aria-label="Trocar ${label}"`));
    }
    assert.ok(s.includes('Busque por nome, CPF ou telefone...'));
    assert.ok(s.includes('Busque por placa, veículo ou montadora...'));
    assert.equal(matches(s, /class="busca-selecao__rodape"/g).length, 2);
    assert.ok(s.includes('Role para visualizar os resultados.'));
    assert.ok(s.includes('Apenas veículos vinculados ao cliente selecionado.'));
    assert.ok(s.includes('Veículos vinculados ao cliente'));
  });
}

test('FE-06: venda de balcao e cadastro rapido da Nota permanecem; OS continua com obrigatoriedade', () => {
  const nota = current.get('nota');
  const os = current.get('os');
  for (const name of ['abrirCadastroClienteRapido', 'criarClienteRapido', 'abrirCadastroVeiculoRapido', 'criarVeiculoRapido']) {
    assert.ok(nota.includes(`wire:click="${name}"`));
    assert.equal(matches(nota, new RegExp(`wire:click="${name}"`, 'g')).length,
      matches(original.get('nota'), new RegExp(`wire:click="${name}"`, 'g')).length);
  }
  assert.ok(nota.includes('Deixe vazio para venda de balcão.'));
  assert.ok(os.includes('Cliente responsável:*'));
  assert.ok(os.includes('Veículo:*'));
  assert.ok(!os.includes('criarClienteRapido'));
});
