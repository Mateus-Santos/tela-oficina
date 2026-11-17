import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const load = (file) => readFileSync(file, 'utf8');
const show = load('resources/views/cliente/showcliente.blade.php');
const list = load('resources/views/cliente/listarcliente.blade.php');
const form = load('resources/views/veiculosclientes/_form.blade.php');
const vehicles = load('resources/views/veiculosclientes/listarveiculosclientes.blade.php');
const controller = load('app/Http/Controllers/VeiculosClientesController.php');
const relation = load('app/Models/Cliente.php');
const selector = load('app/Livewire/Cliente/SeletorCliente.php');

test('FE-05: ficha lista vinculos por relacao real e trata estado vazio', () => {
  assert.match(relation, /function veiculosClientes\(\)/);
  assert.match(show, /loadMissing\('veiculosClientes\.veiculo\.montadora'\)/);
  assert.match(show, /\$veiculosVinculados->isEmpty\(\)/);
  assert.match(show, /@foreach \(\$veiculosVinculados as \$veiculoVinculado\)/);
  assert.match(show, /route\('veiculosclientes\.edit', \$veiculoVinculado->id\)/);
  assert.match(show, /route\('veiculosclientes\.create', \['cliente_id' => \$cliente->id\]\)/);
});

test('FE-05: cadastrar veiculo preserva a escolha do usuario e a selecao na edicao', () => {
  assert.match(selector, /function mount\(array \$clientesSelecionadosIds = \[\]\)/);
  assert.match(form, /old\(\s*'clientes',/);
  assert.match(form, /isset\(\$veiculoscliente\)/);
  assert.match(form, /request\(\)->integer\('cliente_id'\)/);
  assert.match(form, /<livewire:cliente\.seletor-cliente/);
  assert.match(list, /route\('veiculosclientes\.create', \['cliente_id' => \$cliente->id\]\)/);
  assert.match(controller, /'veiculosclientes\.cadastroveiculosclientes'/);
});

test('FE-05: lista de veiculos tem retorno por ID, sem assumir nome unico', () => {
  assert.match(vehicles, /route\('clientes\.show', \$cliente->id\)/);
  assert.doesNotMatch(show, /route\('veiculosclientes\.index', \['cliente' =>/);
  assert.match(show, /Consultar todos os veículos/);
});
