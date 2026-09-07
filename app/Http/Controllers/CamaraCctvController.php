<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CamaraCctv;
use Illuminate\Support\Facades\DB;

class CamaraCctvController
{
    /**
     * GET: Obtener todas las cámaras activas
     */
    public function index(Request $request)
    {
        try {
            $sortBy = $request->query('sort_by', 'date_created');
            $sortOrder = $request->query('sort_order', 'desc');
            
            $allowedSorts = ['id', 'date_created', 'nombre_camara', 'estatus_red'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'date_created';
            }
            $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

            // Aplicamos ordenamiento dinámico y paginación
            $camaras = CamaraCctv::orderBy($sortBy, $sortOrder)->paginate(10);
            
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
    try {
        $camara = CamaraCctv::create([
            'nombre_camara' => $request->nombre_camara,
            'ubicacion' => $request->ubicacion,
            'estatus_red' => $request->estatus_red,
            'stream_url' => $request->stream_url,
            'visible_en_caseta' => $request->visible_en_caseta ? 1 : 0 // <- LÍNEA CLAVE
        ]);
        return response()->json(['success' => true, 'data' => $camara], 201);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

public function update(Request $request, $id)
{
    try {
        $camara = CamaraCctv::findOrFail($id);
        $camara->update([
            'nombre_camara' => $request->nombre_camara,
            'ubicacion' => $request->ubicacion,
            'estatus_red' => $request->estatus_red,
            'stream_url' => $request->stream_url,
            'visible_en_caseta' => $request->visible_en_caseta ? 1 : 0 // <- LÍNEA CLAVE
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
            $camaras = CamaraCctv::where('visible_en_caseta', 1)->orderBy('nombre_camara', 'asc')->get();
            
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