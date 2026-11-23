import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const viewPath = 'resources/views/livewire/nota/seletor-cliente-veiculo.blade.php';
const base = '4888b430a81d8460f4d743f88dedfbef5ae7d1dc';
const before = execFileSync('git', ['show', `${base}:${viewPath}`], {encoding:'utf8'});
const after = readFileSync(viewPath, 'utf8');
const css = readFileSync('resources/scss/_feux_notas_lote07.scss','utf8');
const app = readFileSync('resources/scss/_app.scss','utf8');

test('L07: apenas o contêiner de seletores Livewire foi alterado', () => {
    assert.match(before, /^<div class="row g-3">/);
    assert.equal(after, before.replace(/^<div class="row g-3">/, '<div class="row g-3 feux-nota-seletores">'));
});

test('L07: mantém contratos de busca, vínculo e venda de balcão', () => {
    for (const id of ['busca_cliente_nota','busca_veiculo_nota','cliente_id','veiculo_cliente_id']) {
        assert.ok(after.includes(id), `ausente ${id}`);
    }
    for (const action of ['removerCliente','selecionarCliente','abrirCadastroClienteRapido','criarClienteRapido','removerVeiculo','selecionarVeiculo','abrirCadastroVeiculoRapido','criarVeiculoRapido']) {
        assert.ok(after.includes(action), `ausente ${action}`);
    }
    assert.ok(after.includes('Deixe vazio para venda de balcão.'));
});

test('L07: estilos são restritos ao seletor de Nota e não atingem a OS', () => {
    assert.match(css, /\.cadastro \.feux-nota-seletores\s*\{/);
    assert.match(css, /@media \(min-width: 992px\)/);
    assert.match(css, /@media \(max-width: 575\.98px\)/);
    assert.match(css, /prefers-reduced-motion/);
    assert.doesNotMatch(css, /\.feux-listagem-clientes|\.feux-listagem-notas|\.ordem-servico/);
    assert.doesNotMatch(css, /^\.busca-selecao\s*\{/m);
});

test('L07: SCSS é importado uma única vez após o L06', () => {
    const oldImport="@use './_feux_notas_lote06.scss';";
    const newImport="@use './_feux_notas_lote07.scss';";
    assert.ok(app.includes(`${oldImport}\n${newImport}`));
    assert.equal(app.split(newImport).length-1,1);
});
