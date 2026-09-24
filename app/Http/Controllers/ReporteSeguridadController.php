<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReporteSeguridad;
use Illuminate\Support\Facades\DB;

class ReporteSeguridadController
{
    public function index(Request $request)
    {
        try {
            $sortBy = $request->query('sort_by', 'date_created');
            $sortOrder = strtolower($request->query('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $search = $request->query('search', '');
            
            $allowedSorts = ['id', 'date_created', 'fecha_incidente', 'tipo_incidente', 'estatus'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'date_created';
            }

            // Restauramos el filtro de borrado lógico
            $query = ReporteSeguridad::with(['camara', 'guardia'])->where('status', 1);

            // Búsqueda global incluyendo el ID del reporte
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('id', 'LIKE', "%{$search}%")
                      ->orWhere('tipo_incidente', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%")
                      ->orWhereHas('guardia', function($qGuardia) use ($search) {
                          $qGuardia->where('nombre_completo', 'LIKE', "%{$search}%");
                      });
                });
            }

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
                'estatus' => 'Pendiente',
                'status' => 1 // Asegura que nazca activo
            ]);

            return response()->json(['success' => true, 'data' => $reporte, 'message' => 'Reporte creado exitosamente.'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al crear reporte: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $reporte = ReporteSeguridad::find($id);

        if (!$reporte) {
            return response()->json(['success' => false, 'message' => 'Reporte no encontrado'], 404);
        }

        try {
            if ($request->has('estatus')) {
                $reporte->estatus = $request->estatus;
            }
            if ($request->has('tipo_incidente')) {
                $reporte->tipo_incidente = $request->tipo_incidente;
            }
            if ($request->has('descripcion')) {
                $reporte->descripcion = $request->descripcion;
            }

            $reporte->save();

            return response()->json(['success' => true, 'data' => $reporte, 'message' => 'Reporte actualizado.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $reporte = ReporteSeguridad::find($id);

            if (!$reporte) {
                return response()->json(['success' => false, 'message' => 'Reporte no encontrado'], 404);
            }

            // Restauramos el borrado lógico
            $reporte->status = 0; 
            $reporte->save();

            return response()->json([
                'success' => true,
                'message' => 'Reporte eliminado de la bitácora correctamente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }
}