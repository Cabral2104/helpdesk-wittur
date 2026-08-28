<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EquipoInventario;

class EquipoInventarioController
{
    /**
     * GET: Lista todo el inventario. Permite filtrar por tipo de dispositivo.
     */
    public function index(Request $request)
    {
        try {
            // Inicializamos la consultas
            $query = EquipoInventario::query();

            // Filtrado dinámico: Si React envía la variable 'tipo', filtramos la lista.
            // Ejemplo de petición: /api/equipos?tipo=Switch
            if ($request->has('tipo')) {
                $query->where('tipo_dispositivo', $request->tipo);
            }

            $equipos = $query->get();

            return response()->json([
                'success' => true,
                'data' => $equipos,
                'message' => 'Inventario recuperado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar el inventario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST: Registra un nuevo equipo (Laptop, Monitor, Impresora, etc.)
     */
    public function store(Request $request)
    {
        // 1. Validación flexible adaptada a todo tipo de hardware
        $request->validate([
            'codigo_qr' => 'required|string|unique:equipos_inventario,codigo_qr|max:100',
            'tipo_dispositivo' => 'required|string|max:100', // Ej: Monitor, Switch, Workstation
            'marca_modelo' => 'required|string|max:150',     // Ej: Zebra ZT410, Dell Precision
            'ubicacion' => 'required|string|max:150',
            // El campo IP es opcional (nullable), ya que no todo el hardware está en red
            'ip_address' => 'nullable|ip' 
        ]);

        try {
            $equipo = EquipoInventario::create([
                'codigo_qr' => $request->codigo_qr,
                'tipo_dispositivo' => $request->tipo_dispositivo,
                'marca_modelo' => $request->marca_modelo,
                'ubicacion' => $request->ubicacion,
                'ip_address' => $request->ip_address, // Puede llegar como null y la BD lo aceptará
                'user_create_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $equipo,
                'message' => 'Equipo registrado correctamente en el inventario'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al registrar el equipo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: Muestra el detalle de un equipo en particular.
     */
    public function show(string $id)
    {
        try {
            $equipo = EquipoInventario::find($id);

            if (!$equipo) {
                return response()->json(['success' => false, 'message' => 'Equipo no encontrado'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $equipo
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el equipo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT/PATCH: Actualiza datos del equipo (ej. cambio de ubicación o asignación de IP).
     */
    public function update(Request $request, string $id)
    {
        $equipo = EquipoInventario::find($id);

        if (!$equipo) {
            return response()->json(['success' => false, 'message' => 'Equipo no encontrado'], 404);
        }

        // Al actualizar, el código QR puede quedarse igual, por lo que ignoramos el ID actual en la regla unique
        $request->validate([
            'codigo_qr' => 'sometimes|required|string|max:100|unique:equipos_inventario,codigo_qr,' . $id,
            'tipo_dispositivo' => 'sometimes|required|string|max:100',
            'marca_modelo' => 'sometimes|required|string|max:150',
            'ubicacion' => 'sometimes|required|string|max:150',
            'ip_address' => 'nullable|ip'
        ]);

        try {
            $equipo->update($request->all());
            $equipo->user_edit_id = auth()->id(); 
            $equipo->save();

            return response()->json([
                'success' => true,
                'data' => $equipo,
                'message' => 'Datos del equipo actualizados'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el equipo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE: Da de baja un equipo del inventario (Borrado lógico).
     */
    public function destroy(string $id)
    {
        try {
            $equipo = EquipoInventario::find($id);

            if (!$equipo) {
                return response()->json(['success' => false, 'message' => 'Equipo no encontrado'], 404);
            }

            $equipo->status = 0; // Baja lógica
            $equipo->user_edit_id = auth()->id(); // Registra quién lo dio de baja
            $equipo->save();

            return response()->json([
                'success' => true,
                'message' => 'Equipo dado de baja exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja el equipo: ' . $e->getMessage()
            ], 500);
        }
    }
}