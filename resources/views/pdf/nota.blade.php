<!DOCTYPE html>

<html lang="pt-BR">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>


<title>
    Recibo #{{ str_pad($nota->id, 6, '0', STR_PAD_LEFT) }}
</title>

<style>
    @page {
        margin: 15px 20px;
    }

    body {
        font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
        font-size: 10px;
        color: #333333;
        margin: 0;
        padding: 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    .text-left {
        text-align: left;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .font-bold {
        font-weight: bold;
    }

    /* ============================================================
       CABEÇALHO
    ============================================================ */

    .header-table td {
        padding-bottom: 10px;
        border-bottom: 2px solid #dddddd;
        vertical-align: middle;
    }

    .title {
        font-size: 18px;
        font-weight: bold;
        color: #111111;
    }

    .meta-info {
        font-size: 10px;
        color: #555555;
        margin-top: 3px;
    }

    /* ============================================================
       STATUS
    ============================================================ */

    .badge {
        display: inline-block;
        padding: 2px 6px;
        font-size: 9px;
        font-weight: bold;
        color: #ffffff;
        background-color: #666666;
    }

    .badge-cancelado {
        background-color: #d9534f;
    }

    .badge-finalizado {
        background-color: #5cb85c;
    }

    .badge-pendente {
        background-color: #f0ad4e;
    }

    /* ============================================================
       CLIENTE / VEÍCULO
    ============================================================ */

    .box-info {
        background-color: #f9f9f9;
        border: 1px solid #e0e0e0;
        margin-top: 10px;
        margin-bottom: 12px;
    }

    .box-info td {
        padding: 6px 8px;
        vertical-align: top;
        font-size: 10px;
    }

    .box-title {
        font-size: 9px;
        font-weight: bold;
        color: #777777;
        text-transform: uppercase;
        margin-bottom: 3px;
    }

    /* ============================================================
       MANUTENÇÃO / ÓLEO
    ============================================================ */

    .vehicle-maintenance {
        margin-top: 5px;
        padding-top: 5px;
        border-top: 1px solid #e0e0e0;
    }

    .maintenance-label {
        font-size: 8.5px;
        color: #777777;
    }

    .maintenance-value {
        font-weight: bold;
    }

    .maintenance-success {
        color: #198754;
    }

    .maintenance-warning {
        color: #d39e00;
    }

    .maintenance-danger {
        color: #d9534f;
    }

    /* ============================================================
       TABELA DE ITENS
    ============================================================ */

    .table-items {
        margin-bottom: 12px;
    }

    .table-items th {
        background-color: #333333;
        color: #ffffff;
        font-size: 9.5px;
        padding: 5px 6px;
        text-transform: uppercase;
    }

    .table-items td {
        padding: 5px 6px;
        border-bottom: 1px solid #eeeeee;
        font-size: 9.5px;
    }

    .row-category td {
        background-color: #eaeaea !important;
        font-weight: bold;
        font-size: 9px;
        color: #333333;
        text-transform: uppercase;
        padding: 4px 6px;
        border-top: 1px solid #cccccc;
        border-bottom: 1px solid #cccccc;
    }

    .row-subtotal td {
        background-color: #f4f6f8 !important;
        font-size: 9px;
        color: #222222;
        padding: 5px 6px;
        border-bottom: 1px solid #cccccc;
    }

    /* ============================================================
       RODAPÉ / OBSERVAÇÕES
    ============================================================ */

    .table-footer td {
        vertical-align: top;
    }

    .box-obs {
        border: 1px solid #dddddd;
        background-color: #fafafa;
        padding: 6px 8px;
        font-size: 9px;
        color: #555555;
    }

    /* ============================================================
       RESUMO
    ============================================================ */

    .summary-table {
        width: 100%;
        border: 1px solid #cccccc;
    }

    .summary-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #eeeeee;
        font-size: 10px;
    }

    .summary-table .total-row td {
        background-color: #333333;
        color: #ffffff;
        font-weight: bold;
        font-size: 11px;
        border-bottom: none;
    }

    /* ============================================================
       ASSINATURA
    ============================================================ */

    .signature-area {
        margin-top: 30px;
    }

    .signature-line {
        border-top: 1px solid #999999;
        width: 80%;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
        padding-top: 3px;
        font-size: 9px;
        color: #666666;
    }
</style>


</head>

<body>


<!-- ============================================================
     CABEÇALHO
============================================================= -->

<table class="header-table">
    <tr>

        <td width="60%">

            <div class="title">
                RECIBO #{{ str_pad($nota->id, 6, '0', STR_PAD_LEFT) }}
            </div>

            <div class="meta-info">

                Emissão:

                <strong>
                    {{ optional($nota->created_at)->format('d/m/Y \à\s H:i') ?? 'Não informado' }}
                </strong>

                |

                Status:

                @php
                    $status = strtolower(trim($nota->status ?? 'pendente'));
                @endphp

                @if($status === 'cancelado')

                    <span class="badge badge-cancelado">
                        CANCELADO
                    </span>

                @elseif($status === 'finalizado')

                    <span class="badge badge-finalizado">
                        FINALIZADO
                    </span>

                @else

                    <span class="badge badge-pendente">
                        {{ strtoupper($nota->status ?? 'PENDENTE') }}
                    </span>

                @endif

            </div>

        </td>

        <td width="40%" class="text-right">

            <img
                src="{{ public_path('img/New Logo.png') }}"
                style="max-height: 45px;"
                alt="Logo"
            >

        </td>

    </tr>
</table>


<!-- ============================================================
     INFORMAÇÕES CLIENTE E VEÍCULO
============================================================= -->

@php

    /*
    |--------------------------------------------------------------------------
    | VEÍCULO
    |--------------------------------------------------------------------------
    */

    $veiculoCliente = $nota->veiculosCliente ?? null;


    /*
    |--------------------------------------------------------------------------
    | KM ATUAL
    |--------------------------------------------------------------------------
    */

    $kmAtual =
        data_get($nota, 'km')
        ?? data_get($veiculoCliente, 'km');


    /*
    |--------------------------------------------------------------------------
    | PRÓXIMA TROCA DE ÓLEO
    |--------------------------------------------------------------------------
    */

    $kmProximaTroca =
        data_get($nota, 'km_proxima_troca_oleo')
        ?? data_get($veiculoCliente, 'km_proxima_troca_oleo');


    /*
    |--------------------------------------------------------------------------
    | DISTÂNCIA PARA A PRÓXIMA TROCA
    |--------------------------------------------------------------------------
    */

    $distanciaTrocaOleo = null;

    if (
        is_numeric($kmAtual) &&
        is_numeric($kmProximaTroca)
    ) {
        $distanciaTrocaOleo =
            (int) $kmProximaTroca -
            (int) $kmAtual;
    }

@endphp


<table class="box-info">

    <tr>

        <!-- ====================================================
             CLIENTE
        ===================================================== -->

        <td
            width="50%"
            style="border-right: 1px solid #e0e0e0;"
        >

            <div class="box-title">
                Dados do Cliente
            </div>

            <strong>
                {{ data_get($nota, 'cliente.pessoa.nome', 'Não Informado') }}
            </strong>

            <br>

            Telefone:

            {{ data_get($nota, 'cliente.pessoa.telefone_1')
                ?? data_get($nota, 'cliente.pessoa.telefone_2')
                ?? 'Não informado'
            }}

        </td>


        <!-- ====================================================
             VEÍCULO
        ===================================================== -->

        <td width="50%">

            <div class="box-title">
                Dados do Veículo
            </div>

            <strong>
                Placa:
                {{ data_get($veiculoCliente, 'placa', 'Sem Placa') }}
            </strong>

            <br>

            Modelo:
            {{ data_get($veiculoCliente, 'veiculo.nome', 'N/A') }}

            |

            Ano:
            {{ data_get($veiculoCliente, 'ano', 'N/A') }}


            <!-- ====================================================
                 KM / TROCA DE ÓLEO
            ===================================================== -->

            @if(
                is_numeric($kmAtual) ||
                is_numeric($kmProximaTroca)
            )

                <div class="vehicle-maintenance">

                    @if(is_numeric($kmAtual))

                        <span class="maintenance-label">
                            KM Atual:
                        </span>

                        <span class="maintenance-value">
                            {{ number_format($kmAtual, 0, ',', '.') }} km
                        </span>

                    @endif


                    @if(is_numeric($kmProximaTroca))

                        <br>

                        <span class="maintenance-label">
                            Próxima Troca de Óleo:
                        </span>

                        <span class="maintenance-value">
                            {{ number_format($kmProximaTroca, 0, ',', '.') }} km
                        </span>

                    @endif


                    @if(is_numeric($distanciaTrocaOleo))

                        <br>

                        <span class="maintenance-label">
                            Situação:
                        </span>

                        @if($distanciaTrocaOleo > 0)

                            <span class="maintenance-value maintenance-success">
                                Faltam
                                {{ number_format($distanciaTrocaOleo, 0, ',', '.') }}
                                km para a próxima troca.
                            </span>

                        @elseif($distanciaTrocaOleo === 0)

                            <span class="maintenance-value maintenance-warning">
                                Troca de óleo prevista para o KM atual.
                            </span>

                        @else

                            <span class="maintenance-value maintenance-danger">
                                Troca atrasada em
                                {{ number_format(abs($distanciaTrocaOleo), 0, ',', '.') }}
                                km.
                            </span>

                        @endif

                    @endif

                </div>

            @endif

        </td>

    </tr>

</table>


<!-- ============================================================
     CÁLCULOS E FILTRAGENS
============================================================= -->

@php

    $itens = $nota->itens ?? collect();


    /*
    |--------------------------------------------------------------------------
    | PRODUTOS
    |--------------------------------------------------------------------------
    */

    $produtos = $itens->filter(function ($item) {

        $tipo = strtolower(trim(
            $item->tipo
            ?? $item->tipo_formatado
            ?? ''
        ));

        return !in_array(
            $tipo,
            ['servico', 'serviço', 's']
        );

    });


    /*
    |--------------------------------------------------------------------------
    | SERVIÇOS
    |--------------------------------------------------------------------------
    */

    $servicos = $itens->filter(function ($item) {

        $tipo = strtolower(trim(
            $item->tipo
            ?? $item->tipo_formatado
            ?? ''
        ));

        return in_array(
            $tipo,
            ['servico', 'serviço', 's']
        );

    });


    /*
    |--------------------------------------------------------------------------
    | TOTAIS DOS PRODUTOS
    |--------------------------------------------------------------------------
    */

    $brutoProdutos = $produtos->sum(function ($item) {

        return
            (float) ($item->quantidade ?? 0)
            *
            (float) ($item->valor_unitario ?? 0);

    });


    $descontoProdutos = $produtos->sum(function ($item) {

        return (float) ($item->desconto ?? 0);

    });


    $subtotalProdutos =
        $brutoProdutos -
        $descontoProdutos;


    /*
    |--------------------------------------------------------------------------
    | TOTAIS DOS SERVIÇOS
    |--------------------------------------------------------------------------
    */

    $brutoServicos = $servicos->sum(function ($item) {

        return
            (float) ($item->quantidade ?? 0)
            *
            (float) ($item->valor_unitario ?? 0);

    });


    $descontoServicos = $servicos->sum(function ($item) {

        return (float) ($item->desconto ?? 0);

    });


    $subtotalServicos =
        $brutoServicos -
        $descontoServicos;


    /*
    |--------------------------------------------------------------------------
    | TOTAIS GERAIS
    |--------------------------------------------------------------------------
    */

    $brutoGeral =
        $brutoProdutos +
        $brutoServicos;


    $totalDescontosItens =
        $descontoProdutos +
        $descontoServicos;


    /*
    |--------------------------------------------------------------------------
    | TOTAL FINAL
    |--------------------------------------------------------------------------
    |
    | Se a nota já possui total salvo no banco, utiliza ele.
    | Caso contrário, calcula pelos itens.
    |
    */

    $totalFinal =
        $nota->total !== null
            ? (float) $nota->total
            : ($brutoGeral - $totalDescontosItens);

@endphp


<!-- ============================================================
     TABELA DE ITENS
============================================================= -->

<table class="table-items">

    <thead>

        <tr>

            <th
                width="40%"
                class="text-left"
            >
                Descrição do Item
            </th>

            <th
                width="10%"
                class="text-center"
            >
                Qtd
            </th>

            <th
                width="16%"
                class="text-right"
            >
                Valor Unit.
            </th>

            <th
                width="14%"
                class="text-right"
            >
                Desconto
            </th>

            <th
                width="20%"
                class="text-right"
            >
                Total Item
            </th>

        </tr>

    </thead>


    <tbody>

        <!-- ====================================================
             PRODUTOS
        ===================================================== -->

        @if($produtos->count() > 0)

            <tr class="row-category">

                <td colspan="5">
                    &gt; PRODUTOS / PEÇAS
                </td>

            </tr>


            @foreach($produtos as $item)

                @php

                    $quantidade =
                        (float) ($item->quantidade ?? 0);

                    $valorUnitario =
                        (float) ($item->valor_unitario ?? 0);

                    $desconto =
                        (float) ($item->desconto ?? 0);

                    $itemTotal =
                        ($quantidade * $valorUnitario)
                        - $desconto;

                    $valorExibicao =
                        $item->valor_total !== null
                            ? (float) $item->valor_total
                            : $itemTotal;

                @endphp


                <tr>

                    <td class="text-left">
                        {{ $item->descricao ?? 'Item sem descrição' }}
                    </td>

                    <td class="text-center">
                        {{ $item->quantidade ?? 0 }}
                    </td>

                    <td class="text-right">
                        R$
                        {{ number_format(
                            $valorUnitario,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="text-right">
                        R$
                        {{ number_format(
                            $desconto,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="text-right font-bold">
                        R$
                        {{ number_format(
                            $valorExibicao,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>

                </tr>

            @endforeach


            <!-- SUBTOTAL PRODUTOS -->

            <tr class="row-subtotal">

                <td
                    colspan="3"
                    class="text-right font-bold"
                >
                    Resumo Produtos:
                </td>

                <td class="text-right">

                    Desc:
                    R$
                    {{ number_format(
                        $descontoProdutos,
                        2,
                        ',',
                        '.'
                    ) }}

                </td>

                <td class="text-right font-bold">

                    Bruto:
                    R$
                    {{ number_format(
                        $brutoProdutos,
                        2,
                        ',',
                        '.'
                    ) }}

                    <br>

                    Líquido:
                    R$
                    {{ number_format(
                        $subtotalProdutos,
                        2,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>

        @endif


        <!-- ====================================================
             SERVIÇOS
        ===================================================== -->

        @if($servicos->count() > 0)

            <tr class="row-category">

                <td colspan="5">
                    &gt; SERVIÇOS / MÃO DE OBRA
                </td>

            </tr>


            @foreach($servicos as $item)

                @php

                    $quantidade =
                        (float) ($item->quantidade ?? 0);

                    $valorUnitario =
                        (float) ($item->valor_unitario ?? 0);

                    $desconto =
                        (float) ($item->desconto ?? 0);

                    $itemTotal =
                        ($quantidade * $valorUnitario)
                        - $desconto;

                    $valorExibicao =
                        $item->valor_total !== null
                            ? (float) $item->valor_total
                            : $itemTotal;

                @endphp


                <tr>

                    <td class="text-left">
                        {{ $item->descricao ?? 'Serviço sem descrição' }}
                    </td>

                    <td class="text-center">
                        {{ $item->quantidade ?? 0 }}
                    </td>

                    <td class="text-right">
                        R$
                        {{ number_format(
                            $valorUnitario,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="text-right">
                        R$
                        {{ number_format(
                            $desconto,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td class="text-right font-bold">
                        R$
                        {{ number_format(
                            $valorExibicao,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>

                </tr>

            @endforeach


            <!-- SUBTOTAL SERVIÇOS -->

            <tr class="row-subtotal">

                <td
                    colspan="3"
                    class="text-right font-bold"
                >
                    Resumo Serviços:
                </td>

                <td class="text-right">

                    Desc:
                    R$
                    {{ number_format(
                        $descontoServicos,
                        2,
                        ',',
                        '.'
                    ) }}

                </td>

                <td class="text-right font-bold">

                    Bruto:
                    R$
                    {{ number_format(
                        $brutoServicos,
                        2,
                        ',',
                        '.'
                    ) }}

                    <br>

                    Líquido:
                    R$
                    {{ number_format(
                        $subtotalServicos,
                        2,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>

        @endif


        <!-- ====================================================
             NENHUM ITEM
        ===================================================== -->

        @if($itens->isEmpty())

            <tr>

                <td
                    colspan="5"
                    class="text-center"
                    style="padding: 12px;"
                >
                    Nenhum item cadastrado.
                </td>

            </tr>

        @endif

    </tbody>

</table>


<!-- ============================================================
     TOTAIS E OBSERVAÇÕES
============================================================= -->

<table class="table-footer">

    <tr>

        <!-- ====================================================
             OBSERVAÇÕES / ASSINATURA
        ===================================================== -->

        <td
            width="55%"
            style="padding-right: 15px;"
        >

            @if(!empty($nota->observacao))

                <div class="box-obs">

                    <strong>
                        OBSERVAÇÕES:
                    </strong>

                    <br>

                    {{ $nota->observacao }}

                </div>

            @endif


            <div class="signature-area">

                <div class="signature-line">
                    Assinatura do Cliente
                </div>

            </div>

        </td>


        <!-- ====================================================
             RESUMO FINANCEIRO
        ===================================================== -->

        <td width="45%">

            <table class="summary-table">

                <tr>

                    <td class="text-left">
                        Total Bruto Geral:
                    </td>

                    <td class="text-right">
                        R$
                        {{ number_format(
                            $brutoGeral,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>

                </tr>


                @if($totalDescontosItens > 0)

                    <tr>

                        <td
                            class="text-left"
                            style="color: #d9534f;"
                        >
                            Total de Descontos:
                        </td>

                        <td
                            class="text-right"
                            style="color: #d9534f;"
                        >
                            - R$
                            {{ number_format(
                                $totalDescontosItens,
                                2,
                                ',',
                                '.'
                            ) }}
                        </td>

                    </tr>

                @endif


                <tr class="total-row">

                    <td class="text-left">
                        TOTAL FINAL:
                    </td>

                    <td class="text-right">
                        R$
                        {{ number_format(
                            $totalFinal,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>

                </tr>

            </table>

        </td>

    </tr>

</table>


</body>

</html>
