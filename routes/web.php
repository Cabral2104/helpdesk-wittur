<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CategoriaIncidenciaController;
use App\Http\Controllers\EquipoInventarioController;
use App\Http\Controllers\CamaraCctvController;
use App\Http\Controllers\TicketController;

// Ruta principal (Temporalmente muestra un texto de bienvenida)
Route::get('/', function () {
    return 'Bienvenido al Sistema de Helpdesk y CCTV de Wittur';
});

// Rutas de Recursos (Genera automáticamente las URLs para el CRUD de cada módulo)
Route::resource('departamentos', DepartamentoController::class);
Route::resource('usuarios', UsuarioController::class);
Route::resource('categorias', CategoriaIncidenciaController::class);
Route::resource('equipos', EquipoInventarioController::class);
Route::resource('camaras', CamaraCctvController::class);
Route::resource('tickets', TicketController::class);