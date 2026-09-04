@props([
    'action',
    'id' => 'filtros',
    'collapsible' => false,
    'expanded' => false,
])

<div class="filtros-container">
    <form method="GET" action="{{ $action }}" class="filtros-container__form">

        @if ($collapsible)

            <div class="filtros-container__primary">
                {{ $primary ?? '' }}
            </div>

            <div class="filtros-container__toggle">
                <button
                    type="button"
                    class="btn btn-outline-dark"
                    data-bs-toggle="collapse"
                    data-bs-target="#{{ $id }}-avancados"
                    aria-expanded="{{ $expanded ? 'true' : 'false' }}"
                    aria-controls="{{ $id }}-avancados"
                >
                    <i class="bi bi-funnel"></i>
                    Filtros
                    <i class="bi bi-chevron-down ms-1"></i>
                </button>
            </div>

            <div
                id="{{ $id }}-avancados"
                class="collapse {{ $expanded ? 'show' : '' }} filtros-container__advanced"
            >
                {{ $advanced ?? '' }}
            </div>

        @else

            <div class="filtros-container__advanced filtros-container__advanced--always-visible">
                {{ $slot }}
            </div>

        @endif

    </form>
</div>
