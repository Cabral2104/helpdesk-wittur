<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;

class UsuarioController
{
    public function index()
    {
        try {
            // Traemos a los usuarios con la información de su departamento
            $usuarios = Usuario::with('departamento')->get();
            return response()->json(['success' => true, 'data' => $usuarios], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_nomina' => 'required|string|unique:usuarios,numero_nomina|max:50',
            'nombre_completo' => 'required|string|max:150',
            'departamento_id' => 'required|integer|exists:departamentos,id',
            'rol' => 'required|in:Operador,Tecnico_ICT,Administrador'
        ]);

        try {
            $usuario = Usuario::create([
                'numero_nomina' => $request->numero_nomina,
                'nombre_completo' => $request->nombre_completo,
                'departamento_id' => $request->departamento_id,
                'rol' => $request->rol,
                'user_create_id' => auth()->id() // Asigna al administrador que lo está creando
            ]);

            return response()->json(['success' => true, 'data' => $usuario, 'message' => 'Usuario registrado'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $usuario = Usuario::with('departamento')->find($id);
        if (!$usuario) return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        
        return response()->json(['success' => true, 'data' => $usuario], 200);
    }

    public function update(Request $request, string $id)
    {
        $usuario = Usuario::find($id);
        if (!$usuario) return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);

        $request->validate([
            'numero_nomina' => 'sometimes|required|string|max:50|unique:usuarios,numero_nomina,' . $id,
            'nombre_completo' => 'sometimes|required|string|max:150',
            'departamento_id' => 'sometimes|required|integer|exists:departamentos,id',
            'rol' => 'sometimes|required|in:Operador,Tecnico_ICT,Administrador'
        ]);

        try {
            $usuario->update($request->all());
            $usuario->user_edit_id = auth()->id(); // Registra quién editó
            $usuario->save();

            return response()->json(['success' => true, 'data' => $usuario, 'message' => 'Usuario actualizado'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $usuario = Usuario::find($id);
        if (!$usuario) return response()->json(['success' => false, 'message' => 'No encontrado'], 404);

        $usuario->status = 0;
        $usuario->user_edit_id = auth()->id(); // Registra quién lo dio de baja
        $usuario->save();

        return response()->json(['success' => true, 'message' => 'Usuario dado de baja'], 200);
    }
}