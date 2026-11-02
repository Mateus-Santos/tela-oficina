<div class="modal fade" id="modalCatalogoSimilares" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Produtos similares</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
    </div>
    <div class="modal-body" id="modalCatalogoSimilaresConteudo">Selecione um produto.</div>
  </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('modalCatalogoSimilares');
  if (!modal) return;
  let controlador = null;
  modal.addEventListener('show.bs.modal', async function (evento) {
    const origem = evento.relatedTarget;
    const url = origem && origem.getAttribute('data-url-similares');
    const corpo = document.getElementById('modalCatalogoSimilaresConteudo');
    corpo.textContent = 'Carregando produtos similares...';
    if (controlador) controlador.abort();
    controlador = new AbortController();
    if (!url) return;
    try {
      const resposta = await fetch(url, {signal: controlador.signal, credentials:'same-origin', headers:{'Accept':'text/html'}});
      if (!resposta.ok) throw new Error('HTTP '+resposta.status);
      corpo.innerHTML = await resposta.text();
    } catch (erro) {
      if (erro.name !== 'AbortError') corpo.textContent = 'Não foi possível carregar os similares.';
    }
  });
  modal.addEventListener('hidden.bs.modal', function () { if (controlador) controlador.abort(); });
});
</script>
