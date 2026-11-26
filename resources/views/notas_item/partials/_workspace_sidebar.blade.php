{{-- Painel informativo: espelha o formulario existente, nao controla gravacao ou calculos. --}}
<aside class="feux-notas-workspace__aside" aria-label="Painel de acompanhamento da nota">
    <div class="feux-notas-workspace__aside-inner">
        <div class="feux-notas-workspace__aside-top">
            <span class="feux-notas-workspace__aside-icon" aria-hidden="true"><i class="bi bi-receipt-cutoff"></i></span>
            <div>
                <p class="feux-notas-workspace__aside-kicker">Acompanhamento</p>
                <h2 class="feux-notas-workspace__aside-title">Sua nota, em tempo real</h2>
            </div>
        </div>
        <div class="feux-notas-workspace__value" aria-label="Valor atual da nota">
            <span>Valor atual</span>
            <strong data-feux-workspace-total>R$ 0,00</strong>
            <small>Espelho do resumo do formulário</small>
        </div>
        <dl class="feux-notas-workspace__facts">
            <div><dt><i class="bi bi-bag-check" aria-hidden="true"></i> Itens</dt><dd data-feux-workspace-items aria-live="polite">0 itens</dd></div>
            <div><dt><i class="bi bi-person" aria-hidden="true"></i> Cliente</dt><dd data-feux-workspace-cliente>Venda de balcão</dd></div>
            <div><dt><i class="bi bi-car-front" aria-hidden="true"></i> Veículo</dt><dd data-feux-workspace-veiculo>Não informado</dd></div>
        </dl>
        <div class="feux-notas-workspace__aside-nav">
            <h3>Atalhos da nota</h3>
            <a href="#feux-notas-identificacao"><span>01</span> Cliente e veículo <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <a href="#feux-notas-itens"><span>02</span> Produtos e serviços <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <a href="#feux-notas-descontos"><span>03</span> Descontos <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
        <a class="feux-notas-workspace__save-link" href="#feux-notas-salvar">
            Revisar e salvar <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
        <p class="feux-notas-workspace__aside-note"><i class="bi bi-shield-check" aria-hidden="true"></i> Nenhuma alteração é gravada até você usar Salvar nota.</p>
    </div>
</aside>
