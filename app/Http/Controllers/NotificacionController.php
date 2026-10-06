<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\ReporteSeguridad;
use Carbon\Carbon;

class NotificacionController
{
    public function index()
    {
        try {
            // Límite exacto de 7 días atrás
            $fechaLimite = Carbon::now()->subDays(7);

            // 1. Obtener Tickets recientes
            $tickets = Ticket::where('status', 1)
                ->where('date_created', '>=', $fechaLimite)
                ->get()
                ->map(function($ticket) {
                    // Asignamos peso para ordenar: Alta = 3, Media = 2, Baja = 1
                    $peso = 1;
                    if ($ticket->prioridad === 'Alta') $peso = 3;
                    if ($ticket->prioridad === 'Media') $peso = 2;

                    return [
                        'id' => 'T-' . $ticket->id,
                        'tipo' => 'ticket',
                        'titulo' => 'Nuevo Ticket #' . $ticket->id,
                        'descripcion' => $ticket->descripcion_falla, // Apunta a la columna correcta
                        'prioridad' => $ticket->prioridad,
                        'peso_prioridad' => $peso,
                        'fecha' => $ticket->date_created, // Apunta a la columna correcta
                        'ruta' => '/tickets'
                    ];
                });

            // 2. Obtener Reportes de Seguridad recientes
            $reportes = ReporteSeguridad::where('status', 1)
                ->where('date_created', '>=', $fechaLimite) 
                ->get()
                ->map(function($reporte) {
                    return [
                        'id' => 'R-' . $reporte->id,
                        'tipo' => 'seguridad',
                        'titulo' => 'Novedad de Seguridad #' . $reporte->id,
                        'descripcion' => $reporte->tipo_incidente,
                        'prioridad' => 'Media',
                        'peso_prioridad' => 2, // Se trata como prioridad Media
                        'fecha' => $reporte->date_created, // Apunta a la columna correcta
                        'ruta' => '/reportes-seguridad'
                    ];
                });

            // 3. Unir colecciones y ordenar (Prioridad DESC, Fecha DESC)
            $notificaciones = $tickets->concat($reportes)->sortBy([
                ['peso_prioridad', 'desc'],
                ['fecha', 'desc'],
            ])->values()->all();

            return response()->json([
                'success' => true,
                'data' => $notificaciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}