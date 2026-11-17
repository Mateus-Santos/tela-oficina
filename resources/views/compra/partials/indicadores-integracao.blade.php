@php
    // U02: apenas atributos calculados pelo controller. Nenhuma consulta na view.
    $estoqueConfig = match ($compra->integracao_estoque_status ?? null) {
        'sem_itens' => [
            'class' => 'bg-secondary', 'icon' => 'bi-dash-circle', 'label' => 'Sem itens',
            'descricao' => 'A compra não possui itens registrados.',
        ],
        'pendente' => [
            'class' => 'bg-secondary', 'icon' => 'bi-clock', 'label' => 'Sem entrada',
            'descricao' => 'Nenhum item possui entrada de estoque identificada.',
        ],
        'parcial' => [
            'class' => 'bg-info text-dark', 'icon' => 'bi-hourglass-split', 'label' => 'Entrada parcial',
            'descricao' => 'Somente parte dos itens possui entrada identificada.',
        ],
        'lancado' => [
            'class' => 'bg-success', 'icon' => 'bi-box-seam', 'label' => 'Entrada identificada',
            'descricao' => 'Há entrada histórica por item. Não comprova conciliação de quantidades.',
        ],
        'estornado' => [
            'class' => 'bg-info text-dark', 'icon' => 'bi-arrow-counterclockwise', 'label' => 'Estorno identificado',
            'descricao' => 'Estorno reconhecido por item em compra cancelada; quantidades não conciliadas por este indicador.',
        ],
        'verificar_estorno' => [
            'class' => 'bg-warning text-dark', 'icon' => 'bi-exclamation-triangle', 'label' => 'Conferir estorno',
            'descricao' => 'Compra cancelada com entradas sem estorno correspondente identificado para todos os itens.',
        ],
        'sem_entrada' => [
            'class' => 'bg-secondary', 'icon' => 'bi-slash-circle', 'label' => 'Sem entrada (cancelada)',
            'descricao' => 'Compra cancelada sem entrada de estoque identificada.',
        ],
        default => [
            'class' => 'bg-secondary', 'icon' => 'bi-question-circle', 'label' => 'Não informado',
            'descricao' => 'Indicador de estoque indisponível.',
        ],
    };

    $financeiroConfig = match ($compra->integracao_financeiro_status ?? null) {
        'nao_vinculada' => [
            'class' => 'bg-secondary', 'icon' => 'bi-dash-circle', 'label' => 'Sem vínculo',
            'descricao' => 'Não existe conta a pagar vinculada.',
        ],
        'vinculada' => [
            'class' => 'bg-primary', 'icon' => 'bi-link-45deg', 'label' => 'Conta vinculada',
            'descricao' => 'Existe conta a pagar não cancelada. Vínculo não significa pagamento ou quitação.',
        ],
        'vinculo_cancelado' => [
            'class' => 'bg-warning text-dark', 'icon' => 'bi-link-45deg', 'label' => 'Vínculo cancelado',
            'descricao' => 'Apenas vínculos com contas a pagar canceladas foram identificados.',
        ],
        default => [
            'class' => 'bg-secondary', 'icon' => 'bi-question-circle', 'label' => 'Não informado',
            'descricao' => 'Indicador financeiro indisponível.',
        ],
    };
@endphp

<div class="d-flex flex-column align-items-start gap-1" aria-label="Indicadores de integração">
    <span class="badge {{ $estoqueConfig['class'] }}" title="{{ $estoqueConfig['descricao'] }}">
        <i class="bi {{ $estoqueConfig['icon'] }}" aria-hidden="true"></i>
        Estoque: {{ $estoqueConfig['label'] }}
    </span>
    <span class="badge {{ $financeiroConfig['class'] }}" title="{{ $financeiroConfig['descricao'] }}">
        <i class="bi {{ $financeiroConfig['icon'] }}" aria-hidden="true"></i>
        Financeiro: {{ $financeiroConfig['label'] }}
    </span>
</div>
