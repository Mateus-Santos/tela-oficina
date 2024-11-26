(function() {
    function colaborador(user) {
        let nome =  document.getElementById("name");
        var jsonPessoa= JSON.parse(user);
        nome.value = jsonPessoa.name;
    }
    window.colaborador = colaborador;
})();