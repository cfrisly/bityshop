@extends('layouts.app')

@section('content')
<div class="container">
   <div class="row">
       <div class="col-md-3">
           <div class="card">
               <div class="card-body">
                   <!--<div class="panel panel-default">
                       <div class="panel-heading">
                           {{ Auth::user()->name }}
                       </div>
                   </div>-->
                   <h5>Bienvenido {{ Auth::user()->name }}</h5>
                   <hr>
                   <ul class="list-group list-group-flush">
                       <li class="list-group-item">
                           <a href="#">
                               ❤️ Favoritos
                           </a>
                       </li>

                       <li class="list-group-item">
                           <a href="#">
                               📦 Mis pedidos
                           </a>
                       </li>

                       <li class="list-group-item">
                           <a href="#">
                               📍 Direcciones guardadas
                           </a>
                       </li>

                       <li class="list-group-item">
                           <a href="#">
                               💳 Tarjetas guardadas
                           </a>
                       </li>
                   </ul>
               </div>
           </div>
       </div>

       {{-- Contenido principal --}}
       <div class="col-md-9">
           <div class="card">
               <div class="card-header">
                   <h5>Mis Pedidos</h5>
               </div>

               <div class="card-body">
                   mensaje de orden
               </div>
           </div>
       </div>

       <!--<div class="col-md-8 col-md-offset-2">
           <div class="panel panel-default">
               <div class="panel-heading">Dashboard Pedidos</div>

               <div class="panel-body">
                   @if (session('status'))
                       <div class="alert alert-success">
                           {{ session('status') }}
                       </div>
                   @endif

                   You are logged in! Prueba

               </div>
           </div>
       </div>-->
   </div>
</div>
@endsection
