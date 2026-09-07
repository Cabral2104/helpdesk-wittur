<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReporteSeguridad;
use Illuminate\Support\Facades\DB;

class ReporteSeguridadController
{
    /**
     * GET: Obtener reportes con paginación y filtros
     */
    public function index(Request $request)
    {
        try {
            $sortBy = $request->query('sort_by', 'date_created');
            $sortOrder = strtolower($request->query('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            
            $allowedSorts = ['id', 'date_created', 'fecha_incidente', 'tipo_incidente', 'estatus'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'date_created';
            }

            // Traemos los reportes con la info de la cámara y el guardia que reportó
            $query = ReporteSeguridad::with(['camara', 'guardia']);

            $reportes = $query->orderBy($sortBy, $sortOrder)->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $reportes->items(),
                'meta' => [
                    'current_page' => $reportes->currentPage(),
                    'last_page' => $reportes->lastPage(),
                    'total' => $reportes->total()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener reportes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST: Crear un nuevo reporte desde la caseta
     */
    public function store(Request $request)
    {
        $request->validate([
            'camara_id' => 'nullable|exists:camaras_cctv,id',
            'fecha_incidente' => 'required|date',
            'tipo_incidente' => 'required|string|max:255',
            'descripcion' => 'required|string'
        ]);

        try {
            $reporte = ReporteSeguridad::create([
                'camara_id' => $request->camara_id,
                'usuario_reporta_id' => auth()->id(),
                'fecha_incidente' => $request->fecha_incidente,
                'tipo_incidente' => $request->tipo_incidente,
                'descripcion' => $request->descripcion,
                'estatus' => 'Pendiente'
            ]);

            return response()->json(['success' => true, 'data' => $reporte, 'message' => 'Reporte creado exitosamente.'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al crear reporte: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PUT/PATCH: Actualizar estatus del reporte (Para el Administrador)
     */
    public function update(Request $request, $id)
    {
        $reporte = ReporteSeguridad::find($id);

        if (!$reporte) {
            return response()->json(['success' => false, 'message' => 'Reporte no encontrado'], 404);
        }

        $request->validate([
            'estatus' => 'required|string|in:Pendiente,En_Revision,Resuelto'
        ]);

        try {
            $reporte->update(['estatus' => $request->estatus]);
            return response()->json(['success' => true, 'data' => $reporte, 'message' => 'Estatus actualizado.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }
}