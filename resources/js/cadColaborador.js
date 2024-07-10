(function() {
    function colaborador(cliente) {
        let id_pessoa_html =  document.getElementById("id_pessoa");
        let nome_colaborador =  document.getElementById("nome_colaborador");
        var jsonPessoa= JSON.parse(cliente);
        var nome_pessoa = jsonPessoa.id_pessoa;
        id_pessoa_html.value = nome_pessoa;
    }
    window.colaborador = colaborador;
})();