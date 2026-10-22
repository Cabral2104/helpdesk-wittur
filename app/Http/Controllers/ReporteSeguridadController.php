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

            // Proyectar solo columnas requeridas para el listado y sus relaciones
            $query = ReporteSeguridad::select([
                'id', 'camara_id', 'usuario_reporta_id', 'fecha_incidente',
                'tipo_incidente', 'descripcion', 'estatus', 'date_created'
            ])->with([
                'camara:id,nombre_camara,ubicacion',
                'guardia:id,nombre_completo'
            ])->where('status', 1);

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

        $user = auth()->user();
        $isCreator = (int)$reporte->usuario_reporta_id === (int)$user->id;
        $isAdmin = $user->rol === 'Administrador';

        // Si se intenta modificar el contenido testimonial (tipo, descripción, cámara, fecha):
        // Únicamente el usuario creador del reporte tiene autorización legal/operativa.
        if ($request->has('tipo_incidente') || $request->has('descripcion') || $request->has('camara_id') || $request->has('fecha_incidente')) {
            if (!$isCreator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado: Únicamente el usuario u oficial que redactó este reporte tiene autorización para modificar su contenido.'
                ], 403);
            }
        }

        try {
            // El estatus sí lo puede actualizar el Administrador o el creador
            if ($request->has('estatus')) {
                if (!$isAdmin && !$isCreator) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para cambiar el estatus de este reporte.'
                    ], 403);
                }
                $reporte->estatus = $request->estatus;
            }

            if ($isCreator) {
                if ($request->has('tipo_incidente')) {
                    $reporte->tipo_incidente = $request->tipo_incidente;
                }
                if ($request->has('descripcion')) {
                    $reporte->descripcion = $request->descripcion;
                }
                if ($request->has('camara_id')) {
                    $reporte->camara_id = $request->camara_id;
                }
                if ($request->has('fecha_incidente')) {
                    $reporte->fecha_incidente = $request->fecha_incidente;
                }
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

            $user = auth()->user();
            $isAdmin = $user->rol === 'Administrador';
            $isCreator = (int)$reporte->usuario_reporta_id === (int)$user->id;

            if (!$isAdmin && !$isCreator) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para dar de baja este reporte.'
                ], 403);
            }

            // Borrado lógico oficial
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