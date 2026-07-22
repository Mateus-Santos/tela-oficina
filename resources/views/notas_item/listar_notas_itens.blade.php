@extends('layouts.layout')

@section('content')

<div class="container cadastro">
    <h1>LISTAR NOTAS</h1>

    <div class="filtros-container mb-4">
        <form method="GET" action="{{ route('notas.index') }}" class="d-flex gap-2 align-items-center">
            <input 
                type="text" 
                name="cliente" 
                class="form-control" 
                placeholder="Nome do cliente..." 
                value="{{ request('cliente') }}"
            >

            <select name="status" class="form-select">
                <option value="">Status (Ativos por padrão)</option>
                <option value="Aberto" {{ request('status') == 'Aberto' ? 'selected' : '' }}>Aberto</option>
                <option value="Andamento" {{ request('status') == 'Andamento' ? 'selected' : '' }}>Em Andamento</option>
                <option value="Concluido" {{ request('status') == 'Concluido' ? 'selected' : '' }}>Concluído</option>
                <option value="Cancelado" {{ request('status') == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
            </select>

            <button class="btn btn-warning" type="submit">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            
            <a href="{{ route('notasitem.index') }}" class="btn btn-secondary">
                <i class="bi bi-filter"></i> Limpar Filtros
            </a>
        </form>
    </div>

    {{-- Tabela de Notas --}}
    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">STATUS</th>
                <th scope="col">Cliente</th>
                <th scope="col">Placa Veículo</th>
                <th scope="col">Imprimir</th>
                <th scope="col">Detalhes</th>
                <th scope="col">Excluir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notas as $nota)
            <tr>
                <td>{{ $nota->id }}</td>
                <td>
                    @livewire('status-nota-selector', ['nota' => $nota], key($nota->id))
                </td>
                <td>{{ $nota->cliente?->pessoa?->nome ?? 'Cliente Geral / Balcão' }}</td>
                <td>{{ $nota->veiculoscliente?->placa ?? 'N/A' }}</td>
                <td>
                    <a href="{{ route('notas.pdf', $nota->id) }}" target="_blank" class="btn btn-danger">
                        <i class="bi bi-printer"></i> PDF
                    </a>
                </td>
                <td>
                    <a href="/notasitens/{{$nota->id}}" class="btn btn-success">
                        <i class="bi bi-list-task"></i>
                    </a>
                </td>
                <td>
                    <form action="/notasitens/{{$nota->id}}" method="post" onsubmit="return confirm('Deseja realmente excluir esta nota?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger delete-btn">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection