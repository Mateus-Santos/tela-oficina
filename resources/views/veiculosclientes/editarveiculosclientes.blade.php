@extends('layouts.layout')

@vite(['resources/js/validateForm.js'])

@section('content')
<section class="container cadastro">
  <h1><i class="bi bi-gear"></i> EDITAR DE VEÍCULOS</h1>
  <div class="campos">

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif
    
    <form action="{{ route('veiculosclientes.update', $veiculoscliente->id) }}" method="POST" class="row g-3">
      @csrf
      @method('PATCH')
        <div class="row mb-3">
          @if(auth()->user() && auth()->user()->permitions == 1)
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label" for="id_cliente">Usuário:*</label>
                <select class="form-control" id="id_cliente" name="id_cliente" required>
                <option value="{{$veiculoscliente->cliente->id}}">{{$veiculoscliente->cliente->pessoa->nome}}</option>
                    @foreach($users as $user)
                        @if($user->pessoa->cliente)
                            <option value="{{$user->pessoa->cliente->id}}">{{$user->pessoa->nome}}</option>
                        @endif
                    @endforeach
                </select>
              </div>
            </div>
          @else
            <div class="row mb">
                <div class="col-md-2">
                    <label class="form-label" for="id_cliente">{{auth()->user()->pessoa->nome}}</label>
                    <input class="form-control" type="text" name="id_cliente" id="id_cliente" value="{{auth()->user()->pessoa->cliente->id}}" readonly>
                </div>
            </div>
          @endif
          <div class="col-md-4">
            <label class="form-label" for="montadora">Montadora:*</label>
            <select class="form-control" id="montadora" name="montadora" required>
                @foreach($montadoras as $montadora)
                    <option 
                        value="{{ $montadora->id }}"
                        {{ $montadora->id == $veiculoscliente->veiculo->montadora->id ? 'selected' : '' }}
                    >
                        {{ $montadora->nome }}
                    </option>
                @endforeach
            </select>

          </div>
          <div class="col-md-4">
            <label class="form-label" for="veiculo_id">Veiculo:*</label>
            <select id="veiculo_id" name="veiculo_id" class="form-control">
                <option value="">Selecione a montadora primeiro</option>
            </select>
          </div>
        </div>

        <div class="row mb">
        <div class="col-md-4">
            <label class="form-label" for="placa">Placa:*</label>
            <input type="text" class="form-control" value={{$veiculoscliente->placa}} id="placa" name="placa" placeholder="Digite a placa do veículo" maxlength="8" required>
          </div>
          <div class="col-md-2">
            <label class="form-label" for="ano">Ano:*</label>
            <input type="number" class="form-control" value={{$veiculoscliente->ano}} id="ano" name="ano" placeholder="ex.: 2022" min="1900" max="{{ date('Y') }}" required>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="cor">Cor:*</label>
            <input type="text" class="form-control" value={{$veiculoscliente->cor}} id="cor" name="cor" placeholder="Digite a cor do veículo" maxlength="30" required>
          </div>
        </div>
        <div class="col text-center mt-4">
          <button type="submit" class="btn btn-info"><i class="bi bi-pencil-square"></i> Editar Veículo</button>
        </div>
    </form>
  </section>
  </div>
</section>
@endsection

@section('scripts')
<script>
    var veiculoSelecionado = "{{ $veiculoscliente->veiculo_id }}";
</script>

@vite(['resources/js/cadVeiculo.js'])
@endsection
