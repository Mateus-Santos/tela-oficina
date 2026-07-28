<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Nota Nº {{ $nota->id }}</title>
    <style>
        /* Estilos base para PDF */
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            font-size: 12px;
            color: #333;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Cabeçalho */
        .header td {
            vertical-align: top;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 5px;
        }
        .header-info {
            font-size: 11px;
            color: #555;
            line-height: 1.4;
        }
        .logo-img {
            max-height: 60px;
            width: auto;
        }

        /* Badges de Status otimizadas para Dompdf */
        .badge {
            display: inline;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            color: #ffffff;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-cancelado { background-color: #e53e3e; }
        .badge-finalizado { background-color: #38a169; }
        .badge-pendente   { background-color: #d69e2e; }

        /* Tabela de Informações */
        .info-table {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .info-table td {
            padding: 10px;
            vertical-align: top;
        }
        .info-title {
            font-size: 10px;
            font-weight: bold;
            color: #718096;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .info-value {
            font-size: 12px;
            font-weight: bold;
            color: #2d3748;
            line-height: 1.3;
        }
        .info-sub {
            font-size: 11px;
            font-weight: normal;
            color: #4a5568;
        }

        /* Tabela de Itens */
        .table-data th {
            background-color: #2d3748;
            color: #ffffff;
            font-size: 11px;
            padding: 8px;
            text-transform: uppercase;
        }
        .table-data td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-data tr:nth-child(even) {
            background-color: #f7fafc;
        }

        /* Caixa de Totais */
        .total-box {
            width: 45%;
            margin-left: auto;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .total-box td {
            padding: 6px 10px;
            border-bottom: 1px solid #edf2f7;
            font-size: 11px;
        }
        .total-box tr:last-child td {
            border-bottom: none;
            font-size: 13px;
            font-weight: bold;
            background-color: #edf2f7;
        }
        .total-box .label {
            color: #4a5568;
        }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td>
                <div class="title">
                    RECIBO #000{{ $nota->id }}
                </div>

                <div class="header-info">
                    Data: <strong>{{ $nota->created_at->format('d/m/Y H:i') }}</strong><br>
                    Status:
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

            <td class="text-right">
                <img src="{{ public_path('img/New Logo.png') }}" class="logo-img">
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td width="50%">
                <div class="info-title">CLIENTE</div>
                <div class="info-value">
                    {{ $nota->cliente->pessoa->nome ?? 'Não Informado' }}
                </div>
                <div class="info-sub">
                    Tel: {{ $nota->cliente->pessoa->telefone_1 ?? $nota->cliente->pessoa->telefone_2 ?? 'N/A' }}
                </div>
            </td>

            <td width="50%">
                <div class="info-title">VEÍCULO</div>
                <div class="info-value">
                    {{ $nota->veiculosCliente->placa ?? 'Sem Placa' }}
                </div>
                <div class="info-sub">
                    Modelo: {{ $nota->veiculosCliente->veiculo->nome ?? 'N/A' }} 
                    | Ano: {{ $nota->veiculosCliente->ano ?? 'N/A' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="table-data">
        <thead>
            <tr>
                <th width="12%">Tipo</th>
                <th width="38%">Descrição</th>
                <th width="8%" class="text-center">Qtd</th>
                <th width="14%" class="text-right">Valor Unit.</th>
                <th width="13%" class="text-right">Desconto</th>
                <th width="15%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($nota->itens as $item)
                <tr>
                    <td class="text-center">
                        {{ $item->tipo_formatado }}
                    </td>
                    <td>
                        {{ $item->descricao }}
                    </td>
                    <td class="text-center">
                        {{ $item->quantidade }}
                    </td>
                    <td class="text-right">
                        R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}
                    </td>
                    <td class="text-right">
                        R$ {{ number_format($item->desconto ?? 0, 2, ',', '.') }}
                    </td>
                    <td class="text-right">
                        R$ {{ number_format($item->valor_total ?? (($item->quantidade * $item->valor_unitario) - ($item->desconto ?? 0)), 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">
                        Nenhum item encontrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $subtotalProdutos = 0;
        $subtotalServicos = 0;

        foreach ($nota->itens as $item) {
            $valorItem = $item->valor_total ?? (($item->quantidade * $item->valor_unitario) - ($item->desconto ?? 0));
            $tipo = strtolower($item->tipo ?? $item->tipo_formatado ?? '');

            if (in_array($tipo, ['servico', 'serviço', 's'])) {
                $subtotalServicos += $valorItem;
            } else {
                $subtotalProdutos += $valorItem;
            }
        }
    @endphp

    <table class="total-box">
        <tr>
            <td class="label">Subtotal de Produtos</td>
            <td class="text-right">
                R$ {{ number_format($subtotalProdutos, 2, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="label">Subtotal de Serviços</td>
            <td class="text-right">
                R$ {{ number_format($subtotalServicos, 2, ',', '.') }}
            </td>
        </tr>
        @if(($nota->desconto ?? 0) > 0)
        <tr>
            <td class="label">Desconto Geral</td>
            <td class="text-right">
                - R$ {{ number_format($nota->desconto, 2, ',', '.') }}
            </td>
        </tr>
        @endif
        <tr>
            <td>TOTAL DA NOTA</td>
            <td class="text-right">
                R$ {{ number_format($nota->total, 2, ',', '.') }}
            </td>
        </tr>
    </table>

</body>
</html>