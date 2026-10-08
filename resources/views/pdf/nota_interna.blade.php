<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta
        http-equiv="Content-Type"
        content="text/html; charset=utf-8"
    >

    <title>
        Documento Interno
        #{{ str_pad($nota->id, 6, '0', STR_PAD_LEFT) }}
    </title>

    <style>

        @page {
            margin: 15px 20px;
        }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9px;
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

        .internal-warning {
            margin-top: 3px;
            font-size: 8px;
            font-weight: bold;
            color: #d9534f;
        }

        .meta-info {
            font-size: 9px;
            color: #555555;
            margin-top: 4px;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8px;
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

        .box-info {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            margin-top: 10px;
            margin-bottom: 12px;
        }

        .box-info td {
            padding: 6px 8px;
            vertical-align: top;
            font-size: 9px;
        }

        .box-title {
            font-size: 8px;
            font-weight: bold;
            color: #777777;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .vehicle-maintenance {
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid #e0e0e0;
        }

        .maintenance-label {
            font-size: 8px;
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

        .table-items {
            margin-bottom: 12px;
        }

        .table-items th {
            background-color: #333333;
            color: #ffffff;
            font-size: 8px;
            padding: 5px;
            text-transform: uppercase;
        }

        .table-items td {
            padding: 5px;
            border-bottom: 1px solid #eeeeee;
            font-size: 8px;
            vertical-align: middle;
        }

        .row-category td {
            background-color: #eaeaea !important;
            font-weight: bold;
            font-size: 8px;
            color: #333333;
            text-transform: uppercase;
            padding: 4px 6px;
            border-top: 1px solid #cccccc;
            border-bottom: 1px solid #cccccc;
        }

        .row-subtotal td {
            background-color: #f4f6f8 !important;
            font-size: 8px;
            color: #222222;
            padding: 5px 6px;
            border-bottom: 1px solid #cccccc;
        }

        .product-image {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .product-image-cell {
            width: 58px;
            text-align: center;
            vertical-align: middle;
        }

        .product-code {
            font-weight: bold;
            font-size: 8px;
            color: #111111;
        }

        .product-barcode {
            margin-top: 2px;
            font-size: 7px;
            color: #666666;
        }

        .product-brand {
            font-weight: bold;
            font-size: 8px;
        }

        .product-description-secondary {
            display: block;
            margin-top: 2px;
            font-size: 7px;
            color: #666666;
        }

        .no-image {
            font-size: 7px;
            color: #999999;
        }

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

        .summary-table {
            width: 100%;
            border: 1px solid #cccccc;
        }

        .summary-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #eeeeee;
            font-size: 9px;
        }

        .summary-table .total-row td {
            background-color: #333333;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
            border-bottom: none;
        }

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


{{-- ============================================================
    CABEÇALHO
============================================================= --}}

<table class="header-table">

    <tr>

        <td width="65%">

            <div class="title">
                DOCUMENTO INTERNO
                #{{ str_pad($nota->id, 6, '0', STR_PAD_LEFT) }}
            </div>

            <div class="internal-warning">
                USO INTERNO — NÃO ENTREGAR AO CLIENTE
            </div>

            <div class="meta-info">

                Emissão:

                <strong>
                    {{
                        optional(
                            $nota->created_at
                        )->format(
                            'd/m/Y \à\s H:i'
                        )
                        ?? 'Não informado'
                    }}
                </strong>

                |

                Status:

                @php
                    $status =
                        strtolower(
                            trim(
                                $nota->status
                                ?? 'pendente'
                            )
                        );
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
                        {{
                            strtoupper(
                                $nota->status
                                ?? 'PENDENTE'
                            )
                        }}
                    </span>

                @endif

            </div>

        </td>

        <td
            width="35%"
            class="text-right"
        >

            <img
                src="{{ public_path('img/New Logo.png') }}"
                style="max-height: 45px;"
                alt="Logo"
            >

        </td>

    </tr>

</table>


{{-- ============================================================
    CLIENTE / VEÍCULO
============================================================= --}}

@php

    $veiculoCliente =
        $nota->veiculosCliente
        ?? null;

    $kmAtual =
        data_get(
            $nota,
            'km'
        )
        ?? data_get(
            $veiculoCliente,
            'km'
        );

    $kmProximaTroca =
        data_get(
            $nota,
            'km_proxima_troca_oleo'
        )
        ?? data_get(
            $veiculoCliente,
            'km_proxima_troca_oleo'
        );

    $distanciaTrocaOleo = null;

    if (
        is_numeric($kmAtual)
        && is_numeric($kmProximaTroca)
    ) {
        $distanciaTrocaOleo =
            (int) $kmProximaTroca
            - (int) $kmAtual;
    }

@endphp


<table class="box-info">

    <tr>

        <td
            width="50%"
            style="border-right: 1px solid #e0e0e0;"
        >

            <div class="box-title">
                Dados do Cliente
            </div>

            <strong>
                {{
                    data_get(
                        $nota,
                        'cliente.pessoa.nome',
                        'Não Informado'
                    )
                }}
            </strong>

            <br>

            Telefone:

            {{
                data_get(
                    $nota,
                    'cliente.pessoa.telefone_1'
                )
                ?? data_get(
                    $nota,
                    'cliente.pessoa.telefone_2'
                )
                ?? 'Não informado'
            }}

        </td>


        <td width="50%">

            <div class="box-title">
                Dados do Veículo
            </div>

            <strong>
                Placa:
                {{
                    data_get(
                        $veiculoCliente,
                        'placa',
                        'Sem Placa'
                    )
                }}
            </strong>

            <br>

            Modelo:
            {{
                data_get(
                    $veiculoCliente,
                    'veiculo.nome',
                    'N/A'
                )
            }}

            |

            Ano:
            {{
                data_get(
                    $veiculoCliente,
                    'ano',
                    'N/A'
                )
            }}


            @if(
                is_numeric($kmAtual)
                || is_numeric($kmProximaTroca)
            )

                <div class="vehicle-maintenance">

                    @if(is_numeric($kmAtual))

                        <span class="maintenance-label">
                            KM Atual:
                        </span>

                        <span class="maintenance-value">
                            {{
                                number_format(
                                    $kmAtual,
                                    0,
                                    ',',
                                    '.'
                                )
                            }}
                            km
                        </span>

                    @endif


                    @if(is_numeric($kmProximaTroca))

                        <br>

                        <span class="maintenance-label">
                            Próxima Troca de Óleo:
                        </span>

                        <span class="maintenance-value">
                            {{
                                number_format(
                                    $kmProximaTroca,
                                    0,
                                    ',',
                                    '.'
                                )
                            }}
                            km
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
                                {{
                                    number_format(
                                        $distanciaTrocaOleo,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                                km para a próxima troca.
                            </span>

                        @elseif($distanciaTrocaOleo === 0)

                            <span class="maintenance-value maintenance-warning">
                                Troca de óleo prevista para o KM atual.
                            </span>

                        @else

                            <span class="maintenance-value maintenance-danger">
                                Troca atrasada em
                                {{
                                    number_format(
                                        abs($distanciaTrocaOleo),
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                                km.
                            </span>

                        @endif

                    @endif

                </div>

            @endif

        </td>

    </tr>

</table>


{{-- ============================================================
    CÁLCULOS
============================================================= --}}

@php

    $itens =
        $nota->itens
        ?? collect();

    $produtos =
        $itens->filter(
            function ($item) {
                return
                    $item->itemable_type
                    === \App\Models\Produto::class;
            }
        );

    $servicos =
        $itens->filter(
            function ($item) {
                return
                    $item->itemable_type
                    === \App\Models\OrdemServico::class;
            }
        );

    $brutoProdutos =
        $produtos->sum(
            function ($item) {
                return
                    (float) ($item->quantidade ?? 0)
                    *
                    (float) ($item->valor_unitario ?? 0);
            }
        );

    $descontoProdutos =
        $produtos->sum(
            function ($item) {
                return
                    (float) ($item->desconto ?? 0);
            }
        );

    $subtotalProdutos =
        $brutoProdutos
        - $descontoProdutos;

    $brutoServicos =
        $servicos->sum(
            function ($item) {
                return
                    (float) ($item->quantidade ?? 0)
                    *
                    (float) ($item->valor_unitario ?? 0);
            }
        );

    $descontoServicos =
        $servicos->sum(
            function ($item) {
                return
                    (float) ($item->desconto ?? 0);
            }
        );

    $subtotalServicos =
        $brutoServicos
        - $descontoServicos;

    $brutoGeral =
        $brutoProdutos
        + $brutoServicos;

    $totalDescontosItens =
        $descontoProdutos
        + $descontoServicos;

    $totalFinal =
        $nota->total !== null
            ? (float) $nota->total
            : (
                $brutoGeral
                - $totalDescontosItens
            );

@endphp


{{-- ============================================================
    PRODUTOS
============================================================= --}}

@if($produtos->count() > 0)

    <table class="table-items">

        <thead>

            <tr>

                <th width="7%" class="text-center">
                    Imagem
                </th>

                <th width="12%" class="text-left">
                    Código
                </th>

                <th width="11%" class="text-left">
                    Marca
                </th>

                <th width="27%" class="text-left">
                    Descrição
                </th>

                <th width="6%" class="text-center">
                    Qtd.
                </th>

                <th width="12%" class="text-right">
                    Valor Unit.
                </th>

                <th width="11%" class="text-right">
                    Desconto
                </th>

                <th width="14%" class="text-right">
                    Total
                </th>

            </tr>

        </thead>

        <tbody>

            <tr class="row-category">
                <td colspan="8">
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

                    $produto =
                        $item->itemable instanceof \App\Models\Produto
                            ? $item->itemable
                            : null;

                    $codigoFabricante =
                        $produto?->codigo_fabricante
                        ?: 'N/A';

                    $codigoBarras =
                        $produto?->codigo_barras;

                    $marca =
                        $produto?->marcaRelacionada?->nome
                        ?: 'Não informada';

                    $descricaoProduto =
                        $produto?->descricao;

                    $imagem =
                        $produto?->imagens?->first();

                    $imagemPath = null;

                    if ($imagem?->caminho) {

                        $possivelImagem =
                            storage_path(
                                'app/public/'
                                . ltrim(
                                    $imagem->caminho,
                                    '/'
                                )
                            );

                        if (is_file($possivelImagem)) {
                            $imagemPath =
                                $possivelImagem;
                        }

                    }

                @endphp

                <tr>

                    <td class="product-image-cell">

                        @if($imagemPath)

                            <img
                                src="{{ $imagemPath }}"
                                class="product-image"
                                alt=""
                            >

                        @else

                            <span class="no-image">
                                SEM IMAGEM
                            </span>

                        @endif

                    </td>

                    <td class="text-left">

                        <div class="product-code">
                            {{ $codigoFabricante }}
                        </div>

                        @if($codigoBarras)

                            <div class="product-barcode">
                                EAN:
                                {{ $codigoBarras }}
                            </div>

                        @endif

                    </td>

                    <td class="text-left">

                        <span class="product-brand">
                            {{ $marca }}
                        </span>

                    </td>

                    <td class="text-left">

                        {{
                            $item->descricao
                            ?? 'Item sem descrição'
                        }}

                        @if(
                            $descricaoProduto
                            && trim($descricaoProduto)
                                !== trim(
                                    (string) $item->descricao
                                )
                        )

                            <span class="product-description-secondary">
                                {{ $descricaoProduto }}
                            </span>

                        @endif

                    </td>

                    <td class="text-center">
                        {{ $item->quantidade ?? 0 }}
                    </td>

                    <td class="text-right">
                        R$
                        {{
                            number_format(
                                $valorUnitario,
                                2,
                                ',',
                                '.'
                            )
                        }}
                    </td>

                    <td class="text-right">
                        R$
                        {{
                            number_format(
                                $desconto,
                                2,
                                ',',
                                '.'
                            )
                        }}
                    </td>

                    <td class="text-right font-bold">
                        R$
                        {{
                            number_format(
                                $valorExibicao,
                                2,
                                ',',
                                '.'
                            )
                        }}
                    </td>

                </tr>

            @endforeach


            <tr class="row-subtotal">

                <td
                    colspan="6"
                    class="text-right font-bold"
                >
                    Produtos
                </td>

                <td class="text-right">
                    Descontos:
                    R$
                    {{
                        number_format(
                            $descontoProdutos,
                            2,
                            ',',
                            '.'
                        )
                    }}
                </td>

                <td class="text-right font-bold">

                    Bruto:
                    R$
                    {{
                        number_format(
                            $brutoProdutos,
                            2,
                            ',',
                            '.'
                        )
                    }}

                    <br>

                    Líquido:
                    R$
                    {{
                        number_format(
                            $subtotalProdutos,
                            2,
                            ',',
                            '.'
                        )
                    }}

                </td>

            </tr>

        </tbody>

    </table>

@endif


{{-- ============================================================
    SERVIÇOS
============================================================= --}}

@if($servicos->count() > 0)

    <table class="table-items">

        <thead>

            <tr>

                <th width="47%" class="text-left">
                    Serviço / Descrição
                </th>

                <th width="8%" class="text-center">
                    Qtd.
                </th>

                <th width="15%" class="text-right">
                    Valor Unit.
                </th>

                <th width="15%" class="text-right">
                    Desconto
                </th>

                <th width="15%" class="text-right">
                    Total
                </th>

            </tr>

        </thead>

        <tbody>

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
                        {{
                            $item->descricao
                            ?? 'Serviço sem descrição'
                        }}
                    </td>

                    <td class="text-center">
                        {{ $item->quantidade ?? 0 }}
                    </td>

                    <td class="text-right">
                        R$
                        {{
                            number_format(
                                $valorUnitario,
                                2,
                                ',',
                                '.'
                            )
                        }}
                    </td>

                    <td class="text-right">
                        R$
                        {{
                            number_format(
                                $desconto,
                                2,
                                ',',
                                '.'
                            )
                        }}
                    </td>

                    <td class="text-right font-bold">
                        R$
                        {{
                            number_format(
                                $valorExibicao,
                                2,
                                ',',
                                '.'
                            )
                        }}
                    </td>

                </tr>

            @endforeach


            <tr class="row-subtotal">

                <td
                    colspan="3"
                    class="text-right font-bold"
                >
                    Serviços
                </td>

                <td class="text-right">
                    Descontos:
                    R$
                    {{
                        number_format(
                            $descontoServicos,
                            2,
                            ',',
                            '.'
                        )
                    }}
                </td>

                <td class="text-right font-bold">

                    Bruto:
                    R$
                    {{
                        number_format(
                            $brutoServicos,
                            2,
                            ',',
                            '.'
                        )
                    }}

                    <br>

                    Líquido:
                    R$
                    {{
                        number_format(
                            $subtotalServicos,
                            2,
                            ',',
                            '.'
                        )
                    }}

                </td>

            </tr>

        </tbody>

    </table>

@endif


@if($itens->isEmpty())

    <table class="table-items">

        <tbody>

            <tr>

                <td
                    class="text-center"
                    style="padding: 12px;"
                >
                    Nenhum item cadastrado.
                </td>

            </tr>

        </tbody>

    </table>

@endif


{{-- ============================================================
    RODAPÉ / OBSERVAÇÕES / TOTAL
============================================================= --}}

<table class="table-footer">

    <tr>

        <td
            width="55%"
            style="padding-right: 15px;"
        >

            @if(!empty($nota->observacoes))

                <div class="box-obs">

                    <strong>
                        OBSERVAÇÕES:
                    </strong>

                    <br>

                    {{ $nota->observacoes }}

                </div>

            @endif


            <div class="signature-area">

                <div class="signature-line">
                    Conferência Interna
                </div>

            </div>

        </td>


        <td width="45%">

            <table class="summary-table">

                <tr>

                    <td class="text-left">
                        Total Bruto Geral:
                    </td>

                    <td class="text-right">
                        R$
                        {{
                            number_format(
                                $brutoGeral,
                                2,
                                ',',
                                '.'
                            )
                        }}
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
                            {{
                                number_format(
                                    $totalDescontosItens,
                                    2,
                                    ',',
                                    '.'
                                )
                            }}
                        </td>

                    </tr>

                @endif


                <tr class="total-row">

                    <td class="text-left">
                        TOTAL FINAL:
                    </td>

                    <td class="text-right">
                        R$
                        {{
                            number_format(
                                $totalFinal,
                                2,
                                ',',
                                '.'
                            )
                        }}
                    </td>

                </tr>

            </table>

        </td>

    </tr>

</table>

</body>

</html>
