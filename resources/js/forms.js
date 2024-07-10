let campoCPF =  document.getElementById("cpf");

campoCPF.addEventListener("keypress", function(e) {

    const keyCode = (e.key ? e.key : e.wich);

    let tamanhoCampo = campoCPF.value.length;

    if ("1234567890".indexOf(keyCode) >= 0){
        if(tamanhoCampo == 3 || tamanhoCampo == 7){
            campoCPF.value += ".";
        }

        if(tamanhoCampo == 11){
            campoCPF.value += "-";
        }
    }
    else{
        e.preventDefault();
    }
    
});

let telefone1 =  document.getElementById("telefone_1");

telefone1.addEventListener("keypress", function(e) {

    const keyCode = (e.key ? e.key : e.wich);

    let tamanhoCampo = telefone1.value.length;

    if ("1234567890".indexOf(keyCode) >= 0){
        if(tamanhoCampo == 0){
            telefone1.value += "(";
        }
        
        if(tamanhoCampo == 3){
            telefone1.value += ") ";
        }

        if(tamanhoCampo == 6 && keyCode == 9){
            telefone1.value += " ";
        }

        if(tamanhoCampo == 11 && telefone1.value[5] == 9){
            telefone1.value += "-";
        }else if(tamanhoCampo == 9 && telefone1.value[5] != 9){
            telefone1.value += "-";
        }
    }
    else{
        e.preventDefault();
    }
    
});

let telefone2 =  document.getElementById("telefone_2");

telefone2.addEventListener("keypress", function(e) {

    const keyCode = (e.key ? e.key : e.wich);

    let tamanhoCampo = telefone2.value.length;

    if ("1234567890".indexOf(keyCode) >= 0){
        if(tamanhoCampo == 0){
            telefone2.value += "(";
        }
        
        if(tamanhoCampo == 3){
            telefone2.value += ") ";
        }

        if(tamanhoCampo == 6 && keyCode == 9){
            telefone2.value += " ";
        }

        if(tamanhoCampo == 11 && telefone2.value[5] == 9){
            telefone2.value += "-";
        }else if(tamanhoCampo == 9 && telefone2.value[5] != 9){
            telefone2.value += "-";
        }
    }

    else{
        e.preventDefault();
    }
});