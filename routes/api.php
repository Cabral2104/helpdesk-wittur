<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CategoriaIncidenciaController;
use App\Http\Controllers\EquipoInventarioController;
use App\Http\Controllers\CamaraCctvController;
use App\Http\Controllers\TicketController;

// ==========================================
// RUTAS PÚBLICAS (No requieren Token)
// ==========================================

Route::get('/', function () {
    return response()->json(['message' => 'Bienvenido a la API del Helpdesk de Wittur']);
});

Route::post('/login', [AuthController::class, 'login']);


// ==========================================
// RUTAS PROTEGIDAS (Exigen Token de Sanctum)
// ==========================================

Route::middleware('auth:sanctum')->group(function () {
    
    // Ruta para cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout']);

    // Todos tus módulos (Ahora protegidos y con auth()->id() funcional)
    Route::apiResource('departamentos', DepartamentoController::class);
    Route::apiResource('usuarios', UsuarioController::class);
    Route::apiResource('categorias', CategoriaIncidenciaController::class);
    Route::apiResource('equipos', EquipoInventarioController::class);
    Route::apiResource('cctv', CamaraCctvController::class);
    Route::apiResource('tickets', TicketController::class);
    
});