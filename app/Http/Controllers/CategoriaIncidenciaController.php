<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaIncidencia;

class CategoriaIncidenciaController
{
    // Método GET: Devuelve toda la lista
    public function index()
    {
        try {
            $categorias = CategoriaIncidencia::all();
            
            return response()->json([
                'success' => true,
                'data' => $categorias,
                'message' => 'Categorías recuperadas con éxito'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar las categorías: ' . $e->getMessage()
            ], 500);
        }
    }

    // Método POST: Para guardar una nueva categoría desde React
    public function store(Request $request)
    {
        // 1. Validar los datos que envía React
        $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_incidencias',
            'sla_horas' => 'required|integer|min:1'
        ]);

        try {
            // 2. Crear el registro (Añadiremos tu ID de usuario administrador manualmente por ahora)
            $categoria = CategoriaIncidencia::create([
                'nombre' => $request->nombre,
                'sla_horas' => $request->sla_horas,
                'user_create_id' => auth()->id() // Utilizamos el ID del usuario autenticado 
            ]);

            // 3. Responder a React
            return response()->json([
                'success' => true,
                'data' => $categoria,
                'message' => 'Categoría creada correctamente'
            ], 201); // 201 significa "Creado"

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al crear la categoría'
            ], 500);
        }
    }
}