<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Recibo #{{ str_pad($nota->id, 6, '0', STR_PAD_LEFT) }}</title>
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

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Cabeçalho */
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

        /* Status Badge */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            color: #ffffff;
            background-color: #666666;
        }
        .badge-cancelado { background-color: #d9534f; }
        .badge-finalizado { background-color: #5cb85c; }
        .badge-pendente   { background-color: #f0ad4e; }

        /* Dados Cliente e Veículo */
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

        /* Tabela de Itens */
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

        /* Totais e Observações */
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

        .table-summary {
            border: 1px solid #cccccc;
        }
        .table-summary td {
            padding: 5px 8px;
            border-bottom: 1px solid #eeeeee;
            font-size: 10px;
        }
        .table-summary tr.total-row td {
            background-color: #333333;
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
            border-bottom: none;
        }

        /* Assinatura */
        .signature-line {
            margin-top: 30px;
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

    <!-- CABEÇALHO -->
    <table class="header-table">
        <tr>
            <td width="60%">
                <div class="title">RECIBO #{{ str_pad($nota->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="meta-info">
                    Emissão: <strong>{{ $nota->created_at->format('d/m/Y \à\s H:i') }}</strong> | Status:
                    @php $status = strtolower(trim($nota->status)); @endphp
                    @if($status === 'cancelado')
                        <span class="badge badge-cancelado">CANCELADO</span>
                    @elseif($status === 'finalizado')
                        <span class="badge badge-finalizado">FINALIZADO</span>
                    @else
                        <span class="badge badge-pendente">{{ strtoupper($nota->status) }}</span>
                    @endif
                </div>
            </td>
            <td width="40%" class="text-right">
                <img src="{{ public_path('img/New Logo.png') }}" style="max-height: 45px;" alt="Logo">
            </td>
        </tr>
    </table>

    <!-- INFORMAÇÕES CLIENTE E VEÍCULO -->
    <table class="box-info">
        <tr>
            <td width="50%" style="border-right: 1px solid #e0e0e0;">
                <div class="box-title">Dados do Cliente</div>
                <strong>{{ $nota->cliente->pessoa->nome ?? 'Não Informado' }}</strong><br>
                Telefone: {{ $nota->cliente->pessoa->telefone_1 ?? $nota->cliente->pessoa->telefone_2 ?? 'Não informado' }}
            </td>
            <td width="50%">
                <div class="box-title">Dados do Veículo</div>
                <strong>Placa: {{ $nota->veiculosCliente->placa ?? 'Sem Placa' }}</strong><br>
                Modelo: {{ $nota->veiculosCliente->veiculo->nome ?? 'N/A' }} | Ano: {{ $nota->veiculosCliente->ano ?? 'N/A' }}
            </td>
        </tr>
    </table>

    <!-- CÁLCULOS E FILTRAGENS DE ITENS -->
    @php
        $produtos = $nota->itens->filter(function($i) {
            $t = strtolower($i->tipo ?? $i->tipo_formatado ?? '');
            return !in_array($t, ['servico', 'serviço', 's']);
        });

        $servicos = $nota->itens->filter(function($i) {
            $t = strtolower($i->tipo ?? $i->tipo_formatado ?? '');
            return in_array($t, ['servico', 'serviço', 's']);
        });

        // Totais de Produtos
        $brutoProdutos     = $produtos->sum(fn($i) => $i->quantidade * $i->valor_unitario);
        $descontoProdutos  = $produtos->sum(fn($i) => $i->desconto ?? 0);
        $subtotalProdutos  = $brutoProdutos - $descontoProdutos;

        // Totais de Serviços
        $brutoServicos    = $servicos->sum(fn($i) => $i->quantidade * $i->valor_unitario);
        $descontoServicos = $servicos->sum(fn($i) => $i->desconto ?? 0);
        $subtotalServicos = $brutoServicos - $descontoServicos;

        // Totais Consolidados Gerais
        $brutoGeral           = $brutoProdutos + $brutoServicos;
        $totalDescontosItens  = $descontoProdutos + $descontoServicos;
        $descontoGeral        = $nota->desconto ?? 0;
        $totalDescontosGeral  = $totalDescontosItens + $descontoGeral;
        $totalFinal           = $nota->total ?? ($brutoGeral - $totalDescontosGeral);
    @endphp

    <!-- TABELA DE ITENS -->
    <table class="table-items">
        <thead>
            <tr>
                <th width="40%" class="text-left">Descrição do Item</th>
                <th width="10%" class="text-center">Qtd</th>
                <th width="16%" class="text-right">Valor Unit.</th>
                <th width="14%" class="text-right">Desconto</th>
                <th width="20%" class="text-right">Total Item</th>
            </tr>
        </thead>
        <tbody>
            <!-- SEÇÃO PRODUTOS / PEÇAS -->
            @if($produtos->count() > 0)
                <tr class="row-category">
                    <td colspan="5">&gt; PRODUTOS / PEÇAS</td>
                </tr>
                @foreach($produtos as $item)
                    @php $itemTotal = ($item->quantidade * $item->valor_unitario) - ($item->desconto ?? 0); @endphp
                    <tr>
                        <td class="text-left">{{ $item->descricao }}</td>
                        <td class="text-center">{{ $item->quantidade }}</td>
                        <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->desconto ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right font-bold">R$ {{ number_format($item->valor_total ?? $itemTotal, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <!-- SUB-TOTAL PRODUTOS -->
                <tr class="row-subtotal">
                    <td colspan="3" class="text-right font-bold">Resumo Produtos:</td>
                    <td class="text-right">Desc: R$ {{ number_format($descontoProdutos, 2, ',', '.') }}</td>
                    <td class="text-right font-bold">
                        Bruto: R$ {{ number_format($brutoProdutos, 2, ',', '.') }}<br>
                        Líquido: R$ {{ number_format($subtotalProdutos, 2, ',', '.') }}
                    </td>
                </tr>
            @endif

            <!-- SEÇÃO SERVIÇOS / MÃO DE OBRA -->
            @if($servicos->count() > 0)
                <tr class="row-category">
                    <td colspan="5">&gt; SERVIÇOS / MÃO DE OBRA</td>
                </tr>
                @foreach($servicos as $item)
                    @php $itemTotal = ($item->quantidade * $item->valor_unitario) - ($item->desconto ?? 0); @endphp
                    <tr>
                        <td class="text-left">{{ $item->descricao }}</td>
                        <td class="text-center">{{ $item->quantidade }}</td>
                        <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->desconto ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right font-bold">R$ {{ number_format($item->valor_total ?? $itemTotal, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <!-- SUB-TOTAL SERVIÇOS -->
                <tr class="row-subtotal">
                    <td colspan="3" class="text-right font-bold">Resumo Serviços:</td>
                    <td class="text-right">Desc: R$ {{ number_format($descontoServicos, 2, ',', '.') }}</td>
                    <td class="text-right font-bold">
                        Bruto: R$ {{ number_format($brutoServicos, 2, ',', '.') }}<br>
                        Líquido: R$ {{ number_format($subtotalServicos, 2, ',', '.') }}
                    </td>
                </tr>
            @endif

            @if($nota->itens->isEmpty())
                <tr>
                    <td colspan="5" class="text-center" style="padding: 12px;">Nenhum item cadastrado.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- TOTAIS E OBSERVAÇÕES -->
    <table class="table-footer">
        <tr>
            <td width="55%" style="padding-right: 15px;">
                @if(!empty($nota->observacao))
                    <div class="box-obs">
                        <strong>OBSERVAÇÕES:</strong><br>
                        {{ $nota->observacao }}
                    </div>
                @endif

                <div class="signature-area">
                    <div class="signature-line">
                        Assinatura do Cliente
                    </div>
                </div>
            </td>

            <td width="45%">
                <table class="summary-table">
                    <tr>
                        <td class="text-left">Total Bruto Geral:</td>
                        <td class="text-right">R$ {{ number_format($brutoGeral, 2, ',', '.') }}</td>
                    </tr>
                    @if($totalDescontosGeral > 0)
                    <tr>
                        <td class="text-left" style="color: #d9534f;">Total de Descontos:</td>
                        <td class="text-right" style="color: #d9534f;">- R$ {{ number_format($totalDescontosGeral, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="text-left">TOTAL FINAL:</td>
                        <td class="text-right">R$ {{ number_format($totalFinal, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
