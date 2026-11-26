import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
const files={nota:'resources/views/notas_item/listar_notas_itens.blade.php',os:'resources/views/ordemservico/listar_os.blade.php'};
const current=Object.fromEntries(Object.entries(files).map(([k,p])=>[k,readFileSync(p,'utf8')]));
const old=Object.fromEntries(Object.entries(files).map(([k,p])=>[k,execFileSync('git',['show',`HEAD:${p}`],{encoding:'utf8'})]));
const fields=s=>[...s.matchAll(/<(?:input|select)\b[^>]*\bname="([^"]+)"/gs)].map(x=>x[1]).sort();
const routes=s=>[...s.matchAll(/route\(\s*'([^']+)'/g)].map(x=>x[1]);
for(const k of ['nota','os']){
 test(`FE07 ${k}: preserva nomes de filtros e rotas`,()=>{
  assert.deepEqual(fields(current[k]),fields(old[k]));
  assert.deepEqual(routes(current[k]),routes(old[k]));
  assert.equal(current[k].match(/<x-filtros-container/g)?.length,1);
  assert.match(current[k],/name="status"/);
  assert.match(current[k],/name="etapa_id"/);
 });
 test(`FE07 ${k}: contador usa paginacao real sem consulta adicional`,()=>{
  const varname=k==='nota'?'notas':'ordemservicos';
  assert.match(current[k],new RegExp('\\$'+varname+'->total\\(\\)'));
  assert.match(current[k],/role="status"/);
  assert.match(current[k],/request\(\)->filled\('status'\)/);
  assert.match(current[k],/Somente|somente/i);
  assert.match(current[k],/aria-label="Limpar filtros de/);
 });
}
test('FE07 nota: filtro ID U03 real e botao de filtrar legivel',()=>{
 assert.equal(fields(current.nota).filter(name=>name==='id').length,1);
 assert.match(current.nota,/name="id"\s+type="number"\s+min="1"\s+step="1"/);
 assert.match(current.nota,/>\s*Filtrar\s*<\/button>/);
 assert.match(current.nota,/Aberto \(padrão\)/);
 assert.match(current.nota,/value="Todos"/);
});
test('FE07 OS: filtro ID real continua inteiro positivo e busca por periodo existe',()=>{
 assert.ok(fields(current.os).includes('id'));
 assert.match(current.os,/name="id"\s+min="1"\s+step="1"\s+inputmode="numeric"/);
 for(const name of ['data_inicio','data_fim','placa','descricao','setor'])assert.ok(fields(current.os).includes(name));
 assert.match(current.os,/Nenhuma ordem de serviço aberta no momento/);
 assert.match(current.os,/value="todos"/);
});
