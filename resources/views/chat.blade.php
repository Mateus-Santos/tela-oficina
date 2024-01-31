@extends('layout')

@section('content')


<div class="box-questions">	
          <div class="header">
            <h1>FAÇA SUA PERGUNTA</h1>
          </div>
          <div id="historic"></div>	
          <div class="footer">	
              <input type="text" id="message" placeholder="Pergunte aqui...">	
              @auth	
              <button id="btn-submit">Enviar</button>	
              @endauth
              @guest
              <a class="btn btn-light" href="/login"> Entrar</a>	
              @endguest	
          </div>	
</div>

@endsection