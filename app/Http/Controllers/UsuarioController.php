<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario; // IMPORTANTE: Sin esto, Laravel marca error 500

class UsuarioController
{
    public function index(Request $request)
    {
        try {
            $sortBy = $request->query('sort_by', 'date_created');
            $sortOrder = $request->query('sort_order', 'desc');
            
            // Ya tienes el GlobalScope en el modelo, pero por seguridad lo dejamos
            $usuarios = Usuario::where('status', 1)
                               ->orderBy($sortBy, $sortOrder)
                               ->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $usuarios->items(),
                'meta' => [
                    'current_page' => $usuarios->currentPage(),
                    'last_page' => $usuarios->lastPage(),
                    'total' => $usuarios->total()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_nomina' => 'required|string',
            'nombre_completo' => 'required|string',
            'departamento_id' => 'required|integer',
            'rol' => 'required|string',
        ]);

        try {
            $usuario = Usuario::create([
                'numero_nomina' => $request->numero_nomina,
                'nombre_completo' => $request->nombre_completo,
                'departamento_id' => $request->departamento_id,
                'rol' => $request->rol,
                'status' => 1,
                'user_create_id' => auth()->id() ?? 1
            ]);
            return response()->json(['success' => true, 'data' => $usuario], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'numero_nomina' => 'required|string',
            'nombre_completo' => 'required|string',
            'departamento_id' => 'required|integer',
            'rol' => 'required|string',
        ]);

        try {
            $usuario = Usuario::find($id);
            if(!$usuario) {
                return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }

            $usuario->update([
                'numero_nomina' => $request->numero_nomina,
                'nombre_completo' => $request->nombre_completo,
                'departamento_id' => $request->departamento_id,
                'rol' => $request->rol,
            ]);
            return response()->json(['success' => true, 'data' => $usuario], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $usuario = Usuario::find($id);
            if ($usuario) {
                $usuario->status = 0; // Borrado lógico
                $usuario->save();
            }
            return response()->json(['success' => true, 'message' => 'Usuario dado de baja'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}