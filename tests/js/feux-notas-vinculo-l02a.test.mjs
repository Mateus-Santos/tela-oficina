import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (file) => readFileSync(file, 'utf8');
const file = read('app/Console/Commands/RegularizarOsOrfaNaPreview.php');

test('regularização exige clone, OS 3 e confirmação explícita', () => {
  assert.match(file, /feux_dados_dev_/);
  for (const token of ['--os=', '--cliente=', '--veiculo=', '--valor=', '--executar', '--confirmar=']) {
    assert.ok(file.includes(token), token);
  }
  assert.match(file, /\$osId !== 3/);
  assert.match(file, /\$clienteId !== 6/);
  assert.match(file, /\$veiculoId !== 6/);
  assert.match(file, /\$valorEsperado !== '500\.00'/);
});

test('regularização cria nova Nota com CriarNota dentro de transação', () => {
  assert.match(file, /DB::transaction\(/);
  assert.match(file, /lockForUpdate\(\)/);
  assert.match(file, /\$criarNota->execute\(/);
  assert.match(file, /OrdemServico::class/);
  assert.match(file, /vinculos !== 0/);
});

test('não há vínculo automático com a Nota 59 nem execução financeira', () => {
  assert.doesNotMatch(file, /Nota::find\(59\)|Nota::findOrFail\(59\)/);
  assert.doesNotMatch(file, /FinalizarNota|CriarContaReceber|RegistrarSaida/);
  assert.match(file, /NOTA_59=INTACTA/);
});
