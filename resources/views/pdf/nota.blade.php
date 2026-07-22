<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Nota Nº {{ $nota->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 18px; font-weight: bold; }
        
        .table-data { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-data th, .table-data td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        .table-data th { background-color: #f2f2f2; font-weight: bold; }
        
        /* Helpers de alinhamento e largura */
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        /* Tabela dos Totais */
        .total-box { margin-top: 15px; float: right; width: 260px; }
        .total-box td { padding: 6px 10px; }
        .bg-highlight { background-color: #f9f9f9; }

        /* Estilo para a Logo */
        .logo-img { max-height: 65px; width: auto; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td style="vertical-align: middle;">
                <span class="title">COMPROVANTE DE NOTA #{{ $nota->id }}</span><br>
                Data: {{ $nota->created_at->format('d/m/Y H:i') }}<br>
                Status: <strong>{{ strtoupper($nota->status) }}</strong>
            </td>
            <td class="text-right" style="vertical-align: middle;">
                <img src="{{ public_path('img/New Logo.png') }}" class="logo-img" alt="Logo Oficina">
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td style="width: 50%;"><strong>Cliente:</strong> {{ $nota->cliente->pessoa->nome ?? 'Não Informado' }}</td>
            <td style="width: 50%;"><strong>Veículo:</strong> {{ $nota->veiculosCliente->placa ?? 'N/A' }}</td>
        </tr>
    </table>

    <!-- TABELA DE ITENS COM LARGURAS BEM DEFINIDAS -->
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 10%;">Tipo</th>
                <th style="width: 40%;">Descrição</th>
                <th style="width: 8%;" class="text-center">Qtd</th>
                <th style="width: 14%;" class="text-right">Valor Unit.</th>
                <th style="width: 13%;" class="text-right">Desconto</th>
                <th style="width: 15%;" class="text-right">Total Item</th>
            </tr>
        </thead>
        <tbody>
            @forelse($nota->itens ?? [] as $item)
                <tr>
                    <td class="text-center">{{ $item->tipo_formatado }}</td>
                    <td>{{ $item->descricao }}</td>
                    <td class="text-center">{{ $item->quantidade }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->desconto ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_total ?? ($item->quantidade * $item->valor_unitario), 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Nenhum item vinculado a esta nota.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- BLOCO DE TOTAIS -->
    <table class="total-box table-data">
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td class="text-right">R$ {{ number_format($nota->subtotal, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Desconto:</strong></td>
            <td class="text-right">R$ {{ number_format($nota->desconto, 2, ',', '.') }}</td>
        </tr>
        <tr class="bg-highlight">
            <td><strong>Total Final:</strong></td>
            <td class="text-right"><strong>R$ {{ number_format($nota->total, 2, ',', '.') }}</strong></td>
        </tr>
    </table>

</body>
</html>