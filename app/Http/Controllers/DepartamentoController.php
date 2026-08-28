<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Departamento;

class DepartamentoController
{
    public function index()
    {
        try {
            $departamentos = Departamento::all();
            return response()->json(['success' => true, 'data' => $departamentos], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:departamentos,nombre'
        ]);

        try {
            $departamento = Departamento::create([
                'nombre' => $request->nombre,
                'user_create_id' => auth()->id()
            ]);

            return response()->json(['success' => true, 'data' => $departamento, 'message' => 'Departamento creado exitosamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al crear departamento: ' . $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $departamento = Departamento::find($id);
        
        if (!$departamento) {
            return response()->json(['success' => false, 'message' => 'Departamento no encontrado'], 404);
        }
        
        return response()->json(['success' => true, 'data' => $departamento], 200);
    }

    public function update(Request $request, string $id)
    {
        $departamento = Departamento::find($id);
        
        if (!$departamento) {
            return response()->json(['success' => false, 'message' => 'Departamento no encontrado'], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|required|string|max:100|unique:departamentos,nombre,' . $id
        ]);

        try {
            $departamento->update($request->only(['nombre']));
            $departamento->user_edit_id = auth()->id();
            $departamento->save();

            return response()->json(['success' => true, 'data' => $departamento, 'message' => 'Departamento actualizado'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $departamento = Departamento::find($id);
        
        if (!$departamento) {
            return response()->json(['success' => false, 'message' => 'Departamento no encontrado'], 404);
        }

        try {
            $departamento->status = 0; // Borrado lógico
            $departamento->user_edit_id = auth()->id();
            $departamento->save();

            return response()->json(['success' => true, 'message' => 'Departamento eliminado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}