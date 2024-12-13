(function() {
    function pegar_valor() {
        let valor_total =  document.getElementById("valor_total");
        let quantidade =  document.getElementById("quantidade").value.replace(',','.');
        let valor_uni =  document.getElementById("valor_uni").value.replace(',','.');
        var valor_atual = quantidade * valor_uni;
        let juros =  document.getElementById("juros").value.replace('%','');
        let desconto =  document.getElementById("desconto").value.replace('%','');
        juros = juros/100;
        desconto = desconto/100;
        valor_total.value = (valor_atual * juros) - (valor_atual * desconto) +  valor_atual;
    }
    window.pegar_valor = pegar_valor;

    function valor_unitario(valor_peca) {
        let valor_uni =  document.getElementById("valor_uni");
        var jsonPeca= JSON.parse(valor_peca);
        var precoUni = jsonPeca.preco_uni;
        valor_uni.value = precoUni;
    }
    window.valor_unitario = valor_unitario;
})();
