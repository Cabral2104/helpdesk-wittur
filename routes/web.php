<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CategoriaIncidenciaController;
use App\Http\Controllers\EquipoInventarioController;
use App\Http\Controllers\CamaraCctvController;
use App\Http\Controllers\TicketController;

// Ruta principal de cortesía
Route::get('/', function () {
    return response()->json(['message' => 'API Helpdesk Wittur']);
});