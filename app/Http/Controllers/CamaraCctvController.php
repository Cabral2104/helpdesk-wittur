<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CamaraCctv;
use Illuminate\Support\Facades\DB;

class CamaraCctvController
{
    /**
     * GET: Obtener todas las cámaras activas (con búsqueda y ordenamiento)
     */
    public function index(Request $request)
    {
        try {
            $sortBy = $request->query('sort_by', 'date_created');
            $sortOrder = $request->query('sort_order', 'desc');
            $search = $request->query('search', '');
            
            $allowedSorts = ['id', 'date_created', 'nombre_camara', 'estatus_red'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'date_created';
            }
            $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

            // Base de la consulta: Solo cámaras activas
            $query = CamaraCctv::where('status', 1);

            // Búsqueda en múltiples columnas si hay texto
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre_camara', 'LIKE', "%{$search}%")
                      ->orWhere('ubicacion', 'LIKE', "%{$search}%")
                      ->orWhere('ip_asignada', 'LIKE', "%{$search}%")
                      ->orWhere('numero_serie', 'LIKE', "%{$search}%");
                });
            }

            $camaras = $query->orderBy($sortBy, $sortOrder)->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $camaras->items(),
                'meta' => [
                    'current_page' => $camaras->currentPage(),
                    'last_page' => $camaras->lastPage(),
                    'total' => $camaras->total()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cámaras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST: Registrar una nueva cámara
     */
    public function store(Request $request)
    {
        // Validación básica de seguridad
        $request->validate([
            'nombre_camara' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
            'numero_serie' => 'nullable|string|max:255',
            'ip_asignada' => 'nullable|string|max:50',
            'switch_conexion' => 'nullable|string|max:255',
            'puerto_switch' => 'nullable|string|max:50',
            'stream_url' => 'nullable|string|max:500',
            'estatus_red' => 'required|integer',
            'visible_en_caseta' => 'required|boolean'
        ]);

        try {
            $camara = CamaraCctv::create([
                'nombre_camara' => $request->nombre_camara,
                'ubicacion' => $request->ubicacion,
                'numero_serie' => $request->numero_serie,
                'estatus_red' => $request->estatus_red,
                'stream_url' => $request->stream_url,
                'visible_en_caseta' => $request->visible_en_caseta ? 1 : 0,
                'ip_asignada' => $request->ip_asignada,
                'switch_conexion' => $request->switch_conexion,
                'puerto_switch' => $request->puerto_switch,
                'status' => 1 // Asegurar que nace activa
            ]);
            return response()->json(['success' => true, 'data' => $camara], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT: Actualizar una cámara
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_camara' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
            'numero_serie' => 'nullable|string|max:255',
            'ip_asignada' => 'nullable|string|max:50',
            'switch_conexion' => 'nullable|string|max:255',
            'puerto_switch' => 'nullable|string|max:50',
            'stream_url' => 'nullable|string|max:500',
            'estatus_red' => 'required|integer',
            'visible_en_caseta' => 'required|boolean'
        ]);

        try {
            $camara = CamaraCctv::findOrFail($id);
            $camara->update([
                'nombre_camara' => $request->nombre_camara,
                'ubicacion' => $request->ubicacion,
                'numero_serie' => $request->numero_serie,
                'estatus_red' => $request->estatus_red,
                'stream_url' => $request->stream_url,
                'visible_en_caseta' => $request->visible_en_caseta ? 1 : 0,
                'ip_asignada' => $request->ip_asignada,
                'switch_conexion' => $request->switch_conexion,
                'puerto_switch' => $request->puerto_switch
            ]);
            return response()->json(['success' => true, 'data' => $camara], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE: Borrado lógico de la cámara
     */
    public function destroy($id)
    {
        try {
            $camara = CamaraCctv::find($id);

            if (!$camara) {
                return response()->json(['success' => false, 'message' => 'Cámara no encontrada'], 404);
            }

            $camara->status = 0;
            $camara->user_edit_id = auth()->id();
            $camara->save();

            return response()->json([
                'success' => true,
                'message' => 'Cámara dada de baja correctamente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja la cámara: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: Obtener SOLO las cámaras permitidas para visualización en caseta
     */
    public function camarasCaseta()
    {
        try {
            // Solo trae las que tienen visible_en_caseta = 1 y están activas (status = 1)
            $camaras = CamaraCctv::where('visible_en_caseta', 1)
                                 ->where('status', 1)
                                 ->orderBy('nombre_camara', 'asc')
                                 ->get();
            
            return response()->json([
                'success' => true,
                'data' => $camaras
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cámaras de caseta: ' . $e->getMessage()
            ], 500);
        }
    }
}