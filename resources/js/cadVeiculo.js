$('#montadora').on('change', function () {
    var montadoraId = $(this).val();

    $('#veiculo_id').empty().append('<option>Carregando...</option>');

    if (montadoraId) {

        $.get('/veiculos/montadora/' + montadoraId, function (data) {

            $('#veiculo_id').empty().append('<option value="">Selecione um veículo</option>');

            data.forEach(function (v) {

                var selected = v.id == veiculoSelecionado ? 'selected' : '';

                $('#veiculo_id').append(
                    `<option value="${v.id}" ${selected}>${v.nome}</option>`
                );
            });
        });

    } else {
        $('#veiculo_id').empty().append('<option value="">Selecione a montadora primeiro</option>');
    }
});

$(document).ready(function() {
    $('#montadora').trigger('change');
});
