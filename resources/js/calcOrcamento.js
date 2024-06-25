(function() {
    function pegar_valor() {
        let valor_total =  document.getElementById("valor_total");
        let quantidade =  document.getElementById("quantidade").value.replace(',','.');
        let valor_uni =  document.getElementById("valor_uni").value.replace(',','.');
        valor_total.value = quantidade * valor_uni;
    }
    window.pegar_valor = pegar_valor;
})();