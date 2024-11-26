// Aguarda o carregamento completo do DOM antes de aplicar as máscaras
document.addEventListener("DOMContentLoaded", function () {

  function searchAddresses() {
    const cep = document.getElementById("cep").value.replace(/\D/g, ''); // Remove caracteres não numéricos

    if (cep.length === 8) {
      fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
          if (data.erro) {
            alert("CEP não encontrado!");
          } else {
            document.getElementById("rua").value = data.logradouro;
            document.getElementById("bairro").value = data.bairro;
            document.getElementById("cidade").value = data.localidade;
            document.getElementById("estado").value = data.uf;
          }
        })
        .catch(error => {
          console.error('Erro ao buscar o endereço:', error);
          alert("Erro ao buscar o endereço.");
        });
    } else {
      alert("CEP inválido.");
    }
  }

  // Adicionando o evento blur ao campo CEP
  const cepInput = document.getElementById("cep");
  if (cepInput) {
    cepInput.addEventListener("blur", searchAddresses);
  }

});
