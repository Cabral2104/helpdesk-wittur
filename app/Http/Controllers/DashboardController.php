<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\CamaraCctv;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $isAdmin = $user->rol === 'Administrador';

            $query = Ticket::query();

            if (!$isAdmin) {
                $query->where('usuario_reporta_id', $user->id);
            }

            // 1. Métricas Generales (KPIs)
            $ticketsActivos = (clone $query)->whereIn('estatus', ['Abierto', 'En_Progreso', 'Esperando_Piezas'])->count();
            $ticketsResueltos = (clone $query)->whereIn('estatus', ['Resuelto', 'Cerrado'])->count();
            
            // 2. Gráfica 1: Tickets agrupados por Estatus
            $ticketsPorEstatus = (clone $query)->selectRaw('estatus, COUNT(*) as total')
                                               ->groupBy('estatus')
                                               ->get();

            // 3. Gráfica 2: Tendencia de creación (Últimos 7 días)
            $fechaInicio = Carbon::now()->subDays(7)->startOfDay();
            $ticketsPorDia = (clone $query)
                ->where('date_created', '>=', $fechaInicio)
                ->select(DB::raw('DATE(date_created) as fecha'), DB::raw('COUNT(*) as total'))
                ->groupBy('fecha')
                ->orderBy('fecha', 'asc')
                ->get();

            // 4. Métricas de CCTV (Solo Admin)
            $camarasOffline = $isAdmin ? CamaraCctv::where('estatus_red', 0)->count() : 0;
            $totalCamaras = $isAdmin ? CamaraCctv::count() : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => [
                        'tickets_activos' => $ticketsActivos,
                        'tickets_resueltos' => $ticketsResueltos,
                        'camaras_offline' => $camarasOffline,
                        'camaras_total' => $totalCamaras
                    ],
                    'graficas' => [
                        'tickets_por_estatus' => $ticketsPorEstatus,
                        'tickets_por_dia' => $ticketsPorDia
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las métricas: ' . $e->getMessage()
            ], 500);
        }
    }
}