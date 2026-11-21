@php
                                    $statusConfig = match ($compra->status) {
                                        'pendente' => [
                                            'class' => 'bg-warning text-dark',
                                            'icon' => 'bi-clock',
                                            'label' => 'Pendente',
                                        ],
                                        'conferindo' => [
                                            'class' => 'bg-info text-dark',
                                            'icon' => 'bi-search',
                                            'label' => 'Conferindo',
                                        ],
                                        'aprovada' => [
                                            'class' => 'bg-success',
                                            'icon' => 'bi-check-circle',
                                            'label' => 'Aprovada',
                                        ],
                                        'cancelada' => [
                                            'class' => 'bg-danger',
                                            'icon' => 'bi-x-circle',
                                            'label' => 'Cancelada',
                                        ],
                                        default => [
                                            'class' => 'bg-secondary',
                                            'icon' => 'bi-question-circle',
                                            'label' => ucfirst($compra->status),
                                        ],
                                    };
                                @endphp

<span class="badge {{ $statusConfig['class'] }}">
                                            <i class="bi {{ $statusConfig['icon'] }}"></i>
                                            {{ $statusConfig['label'] }}
                                        </span>
