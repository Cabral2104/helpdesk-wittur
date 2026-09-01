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
    public function index()
    {
        try {
            $camaras = CamaraCctv::orderBy('id', 'desc')->paginate(10);
            
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
        $request->validate([
            'nombre_camara' => 'required|string|max:255|unique:camaras_cctv,nombre_camara',
            'ubicacion' => 'required|string|max:255',
            'stream_url' => 'nullable|string',
            'estatus_red' => 'required|integer|in:0,1,2'
        ]);

        try {
            DB::beginTransaction();

            $camara = CamaraCctv::create([
                'nombre_camara' => $request->nombre_camara,
                'ubicacion' => $request->ubicacion,
                'stream_url' => $request->stream_url ?? 'N/A',
                'estatus_red' => $request->estatus_red,
                'user_create_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $camara,
                'message' => 'Cámara registrada exitosamente.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la cámara: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT/PATCH: Actualizar datos o estatus de una cámara
     */
    public function update(Request $request, $id)
    {
        $camara = CamaraCctv::find($id);

        if (!$camara) {
            return response()->json(['success' => false, 'message' => 'Cámara no encontrada'], 404);
        }

        $request->validate([
            'nombre_camara' => 'sometimes|required|string|max:255|unique:camaras_cctv,nombre_camara,' . $id,
            'ubicacion' => 'sometimes|required|string|max:255',
            'stream_url' => 'nullable|string',
            'estatus_red' => 'sometimes|required|integer|in:0,1,2'
        ]);

        try {
            $datosActualizar = $request->only(['nombre_camara', 'ubicacion', 'estatus_red']);
            $datosActualizar['stream_url'] = $request->stream_url ?? 'N/A';

            $camara->update($datosActualizar);
            $camara->user_edit_id = auth()->id();
            $camara->save();

            return response()->json([
                'success' => true,
                'data' => $camara,
                'message' => 'Cámara actualizada correctamente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cámara: ' . $e->getMessage()
            ], 500);
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
}