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

            // 1. Obtener Tickets recientes (solo columnas necesarias y los 15 más recientes)
            $tickets = Ticket::where('status', 1)
                ->where('date_created', '>=', $fechaLimite)
                ->select(['id', 'descripcion_falla', 'prioridad', 'date_created'])
                ->orderBy('date_created', 'desc')
                ->limit(15)
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
                        'descripcion' => $ticket->descripcion_falla,
                        'prioridad' => $ticket->prioridad,
                        'peso_prioridad' => $peso,
                        'fecha' => $ticket->date_created,
                        'ruta' => '/tickets'
                    ];
                });

            // 2. Obtener Reportes de Seguridad recientes (solo columnas necesarias y los 15 más recientes)
            $reportes = ReporteSeguridad::where('status', 1)
                ->where('date_created', '>=', $fechaLimite) 
                ->select(['id', 'tipo_incidente', 'date_created'])
                ->orderBy('date_created', 'desc')
                ->limit(15)
                ->get()
                ->map(function($reporte) {
                    return [
                        'id' => 'R-' . $reporte->id,
                        'tipo' => 'seguridad',
                        'titulo' => 'Novedad de Seguridad #' . $reporte->id,
                        'descripcion' => $reporte->tipo_incidente,
                        'prioridad' => 'Media',
                        'peso_prioridad' => 2,
                        'fecha' => $reporte->date_created,
                        'ruta' => '/reportes-seguridad'
                    ];
                });

            // 3. Unir colecciones y limitar a 20 notificaciones para el panel
            $notificaciones = $tickets->concat($reportes)->sortBy([
                ['peso_prioridad', 'desc'],
                ['fecha', 'desc'],
            ])->values()->take(20)->all();

            return response()->json([
                'success' => true,
                'data' => $notificaciones
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}