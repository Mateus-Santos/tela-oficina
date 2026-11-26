import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
const read=(path)=>readFileSync(path,'utf8');

test('criar O.S. avulsa exige transacao Nota + item O.S.',()=>{
  const s=read('app/Services/Nota/CriarOsComNota.php');
  assert.match(s,/DB::transaction/);
  assert.match(s,/criarOs->criarParaNota/);
  assert.match(s,/atualizarNota->execute/);
  assert.match(s,/criarNota->execute/);
  assert.match(s,/OrdemServico::class/);
});
test('O.S. rapida do formulario nao faz POST antes de salvar Nota',()=>{
  const s=read('resources/js/notaitem/os-rapida.js');
  assert.match(s,/os_nova_setor_servico_id/);
  assert.match(s,/itemIndex/);
  assert.doesNotMatch(s,/fetch\(\s*['"]\/api\/notas-itens\/ordens-servico/);
  assert.match(s,/id = -\(/);
  assert.match(read('resources/js/notaitem/itens.js'), /O.S. em rascunho/);
});
test('criar e atualizar Notas preservam O.S. em uma transacao',()=>{
  const s=read('app/Actions/Notas/CriarNota.php');
  const u=read('app/Actions/Notas/AtualizarNota.php');
  assert.match(s,/OsRascunhoNaNota::validar/);
  assert.match(s,/OsRascunhoNaNota::criar/);
  assert.match(u,/OsRascunhoNaNota::validar/);
  assert.match(u,/OsRascunhoNaNota::criar/);
  assert.match(u,/osRemovidas/);
  assert.match(u,/Nao e permitido trocar o vinculo/);
});
test('todos os endpoints web OS e API criam Nota junto',()=>{
  const a=read('app/Http/Controllers/OrdemServicoController.php');
  const b=read('app/Http/Controllers/Api/NotaOrdemServicoRapidaController.php');
  assert.match(a,/CriarOsComNota::class/);
  assert.match(b,/CriarOsComNota::class/);
  const c=read('app/Http/Controllers/NotasItemController.php');
  assert.match(c,/A O\.S\. vinculada nao pode ser removida isoladamente/);
});
