<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\CamaraCctv;
use App\Models\ReporteSeguridad; // IMPORTANTE: Agregamos el modelo de seguridad
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $isAdmin = $user->rol === 'Administrador';
            $isSeguridad = $user->rol === 'Seguridad';

            // Variables universales para la respuesta
            $activos = 0;
            $resueltos = 0;
            $porEstatus = [];
            $porDia = [];

            // ==========================================
            // 1. LÓGICA PARA ROL SEGURIDAD (Bitácora)
            // ==========================================
            if ($isSeguridad) {
                $querySeguridad = ReporteSeguridad::query();

                // 1 sola consulta agrupada para todos los estatus
                $porEstatus = (clone $querySeguridad)
                    ->selectRaw('estatus, COUNT(*) as total')
                    ->groupBy('estatus')
                    ->get();

                // Calculamos en memoria sin volver a consultar MySQL
                $activos = (int) $porEstatus->whereIn('estatus', ['Pendiente', 'En_Revision'])->sum('total');
                $resueltos = (int) $porEstatus->where('estatus', 'Resuelto')->sum('total');

                $fechaInicio = Carbon::now()->subDays(7)->startOfDay();
                $porDia = (clone $querySeguridad)
                    ->where('date_created', '>=', $fechaInicio)
                    ->select(DB::raw('DATE(date_created) as fecha'), DB::raw('COUNT(*) as total'))
                    ->groupBy('fecha')
                    ->orderBy('fecha', 'asc')
                    ->get();
            } 
            // ==========================================
            // 2. LÓGICA PARA TI Y ADMIN (Tickets)
            // ==========================================
            else {
                $query = Ticket::query();

                if (!$isAdmin) {
                    $query->where('usuario_reporta_id', $user->id);
                }

                // 1 sola consulta agrupada para todos los estatus
                $porEstatus = (clone $query)
                    ->selectRaw('estatus, COUNT(*) as total')
                    ->groupBy('estatus')
                    ->get();

                // Calculamos en memoria sin consultas extra
                $activos = (int) $porEstatus->whereIn('estatus', ['Abierto', 'En_Progreso', 'Esperando_Piezas'])->sum('total');
                $resueltos = (int) $porEstatus->whereIn('estatus', ['Resuelto', 'Cerrado'])->sum('total');

                $fechaInicio = Carbon::now()->subDays(7)->startOfDay();
                $porDia = (clone $query)
                    ->where('date_created', '>=', $fechaInicio)
                    ->select(DB::raw('DATE(date_created) as fecha'), DB::raw('COUNT(*) as total'))
                    ->groupBy('fecha')
                    ->orderBy('fecha', 'asc')
                    ->get();
            }

            // ==========================================
            // 3. Métricas de CCTV (Solo Admin) - 1 sola consulta
            // ==========================================
            $camarasOffline = 0;
            $totalCamaras = 0;
            if ($isAdmin) {
                $cctvStats = CamaraCctv::selectRaw('COUNT(*) as total, SUM(CASE WHEN estatus_red = 0 THEN 1 ELSE 0 END) as offline')->first();
                $totalCamaras = (int) ($cctvStats->total ?? 0);
                $camarasOffline = (int) ($cctvStats->offline ?? 0);
            }

            // Retornamos el mismo formato exacto que ya tenías
            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => [
                        'tickets_activos' => $activos,
                        'tickets_resueltos' => $resueltos,
                        'camaras_offline' => $camarasOffline,
                        'camaras_total' => $totalCamaras
                    ],
                    'graficas' => [
                        'tickets_por_estatus' => $porEstatus,
                        'tickets_por_dia' => $porDia
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