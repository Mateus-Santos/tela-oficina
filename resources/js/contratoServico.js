(function() {
    function contrato_name_client(contrato_json) {
        let valor_uni =  document.getElementById("contrato_name_client");
        var jsonContrato= JSON.parse(contrato_json);
        var precoUni = jsonContrato.preco_uni;
        valor_uni.value = precoUni;
    }
    window.contrato_json = contrato_json;
})();