<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CategoriaIncidenciaController;
use App\Http\Controllers\EquipoInventarioController;
use App\Http\Controllers\CamaraCctvController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReporteSeguridadController;

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

    // --- 1. RUTAS ESPECÍFICAS (Deben ir primero) ---
    Route::get('/dashboard/metricas', [DashboardController::class, 'index']);
    Route::get('/cctv/caseta', [CamaraCctvController::class, 'camarasCaseta']); // <- ¡Movida arriba!

    // --- 2. RUTAS DE RECURSOS (Dinámicas, deben ir después) ---
    Route::apiResource('departamentos', DepartamentoController::class);
    Route::apiResource('usuarios', UsuarioController::class);
    Route::apiResource('categorias', CategoriaIncidenciaController::class);
    Route::apiResource('equipos', EquipoInventarioController::class);
    Route::get('equipos/{id}/bitacora', [EquipoInventarioController::class, 'getBitacora']);
    Route::post('equipos/{id}/bitacora', [EquipoInventarioController::class, 'storeBitacora']);
    Route::apiResource('cctv', CamaraCctvController::class);
    Route::apiResource('tickets', TicketController::class);
    Route::apiResource('reportes-seguridad', ReporteSeguridadController::class)->except(['create', 'edit', 'destroy']);
    Route::get('/categorias-incidencias', [TicketController::class, 'getCategorias']);
    
});