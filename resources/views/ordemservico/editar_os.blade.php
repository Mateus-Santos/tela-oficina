@extends('layouts.layout')
@section('content')
<div class="container cadastro">
    <h1>Editar OS #{{ $ordemServico->id }}</h1>
    <div class="alert alert-info text-start">A edicao segura preserva cliente, veiculo, etapa e historico. OS vinculada a Nota ou encerrada nao pode ser editada.</div>
    @if($errors->any())<div class="alert alert-danger text-start"><ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif
    <div class="card text-start mb-3"><div class="card-body">
        Cliente: {{ $ordemServico->cliente?->pessoa?->nome ?? 'Nao informado' }}<br>
        Veiculo: {{ $ordemServico->veiculosCliente?->placa ?? 'Nao informado' }}
    </div></div>
    <form method="POST" action="{{ route('ordemservicos.update', $ordemServico) }}" class="text-start">
        @csrf @method('PUT')
        <div class="mb-3"><label class="form-label" for="setor_servico_id">Setor</label>
            <select id="setor_servico_id" class="form-select" name="setor_servico_id" required>
            @foreach($setorservicos as $setor)
                <option value="{{ $setor->id }}" @selected((int) old('setor_servico_id', $ordemServico->setor_servico_id) === (int) $setor->id)>{{ $setor->setor }}</option>
            @endforeach</select></div>
        <div class="mb-3"><label for="descricao" class="form-label">Descricao</label>
            <input id="descricao" name="descricao" class="form-control" maxlength="250" required value="{{ old('descricao', $ordemServico->descricao) }}"></div>
        <div class="mb-3"><label for="valor" class="form-label">Valor (R$)</label>
            <input id="valor" name="valor" class="form-control" inputmode="decimal" required
                pattern="[0-9]{1,9}([,.][0-9]{1,2})?" value="{{ old('valor', number_format((float) $ordemServico->valor, 2, ',', '')) }}">
            <div class="form-text">Formato: 150,00 (sem separador de milhar).</div></div>
        <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
        <a href="{{ route('ordemservicos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    </form>
</div>
@endsection
