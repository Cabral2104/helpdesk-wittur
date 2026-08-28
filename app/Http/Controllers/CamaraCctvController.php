<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CamaraCctv;

class CamaraCctvController
{
    public function index()
    {
        try {
            $camaras = CamaraCctv::all();
            return response()->json(['success' => true, 'data' => $camaras], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_camara' => 'required|string|max:100',
            'ubicacion' => 'required|string|max:150',
            'stream_url' => 'required|string|max:255',
            'estatus_red' => 'boolean'
        ]);

        try {
            $camara = CamaraCctv::create([
                'nombre_camara' => $request->nombre_camara,
                'ubicacion' => $request->ubicacion,
                'stream_url' => $request->stream_url,
                'estatus_red' => $request->estatus_red ?? true,
                'user_create_id' => auth()->id()
            ]);

            return response()->json(['success' => true, 'data' => $camara, 'message' => 'Cámara registrada exitosamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $camara = CamaraCctv::find($id);
        
        if (!$camara) {
            return response()->json(['success' => false, 'message' => 'Cámara no encontrada'], 404);
        }
        
        return response()->json(['success' => true, 'data' => $camara], 200);
    }

    public function update(Request $request, string $id)
    {
        $camara = CamaraCctv::find($id);
        
        if (!$camara) {
            return response()->json(['success' => false, 'message' => 'Cámara no encontrada'], 404);
        }

        $request->validate([
            'nombre_camara' => 'sometimes|required|string|max:100',
            'ubicacion' => 'sometimes|required|string|max:150',
            'stream_url' => 'sometimes|required|string|max:255',
            'estatus_red' => 'sometimes|boolean'
        ]);

        try {
            $camara->update($request->only(['nombre_camara', 'ubicacion', 'stream_url', 'estatus_red']));
            $camara->user_edit_id = auth()->id();
            $camara->save();

            return response()->json(['success' => true, 'data' => $camara, 'message' => 'Datos de la cámara actualizados'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $camara = CamaraCctv::find($id);
        
        if (!$camara) {
            return response()->json(['success' => false, 'message' => 'Cámara no encontrada'], 404);
        }

        try {
            $camara->status = 0; // Borrado lógico
            $camara->user_edit_id = auth()->id();
            $camara->save();

            return response()->json(['success' => true, 'message' => 'Cámara dada de baja del sistema'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}