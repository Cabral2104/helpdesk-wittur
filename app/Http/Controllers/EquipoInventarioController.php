<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EquipoInventario;
use App\Models\BitacoraMantenimiento;
use Illuminate\Support\Facades\DB;

class EquipoInventarioController
{
    // Obtener todos los equipos activos
    public function index(Request $request)
    {
        try {
            // Paginamos de 10 en 10 (puedes ajustar el número)
            $equipos = EquipoInventario::orderBy('id', 'desc')->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $equipos->items(),
                'meta' => [
                    'current_page' => $equipos->currentPage(),
                    'last_page' => $equipos->lastPage(),
                    'total' => $equipos->total()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Crear un nuevo equipo
    public function store(Request $request)
    {
        $request->validate([
            'tipo_dispositivo' => 'required|string',
            'es_critico' => 'required|boolean',
            'marca_modelo' => 'nullable|string',
            'usuario_asignado' => 'nullable|string',
            'etiqueta' => 'nullable|string',
            'prod_id' => 'nullable|string',
            'numero_serie' => 'nullable|string',
            'nombre_red' => 'nullable|string',
            'sistema_operativo' => 'nullable|string',
            'os_build' => 'nullable|string'
        ]);

        try {
            $data = $request->all();
            $data['user_create_id'] = auth()->id();
            
            // 1. Auto-generamos un código QR único
            $data['codigo_qr'] = 'EQP-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // 2. PARCHE DE SEGURIDAD: Llenamos los campos originales obligatorios 
            // mapeándolos con los nuevos datos del formulario para que MySQL no rechace el insert
            $data['ubicacion'] = $request->usuario_asignado ?? 'N/A';
            $data['ip_address'] = $request->nombre_red ?? 'N/A';
            
            $equipo = EquipoInventario::create($data);

            return response()->json([
                'success' => true, 
                'data' => $equipo, 
                'message' => 'Equipo registrado correctamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Actualizar un equipo existente
    public function update(Request $request, $id)
    {
        $equipo = EquipoInventario::find($id);
        if (!$equipo) return response()->json(['success' => false, 'message' => 'Equipo no encontrado'], 404);

        try {
            $data = $request->all();
            $data['user_edit_id'] = auth()->id();
            
            $equipo->update($data);
            
            return response()->json(['success' => true, 'data' => $equipo], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Borrado lógico (cambiar status a 0)
    public function destroy($id)
    {
        $equipo = EquipoInventario::find($id);
        if (!$equipo) return response()->json(['success' => false, 'message' => 'Equipo no encontrado'], 404);
        
        try {
            $equipo->status = 0;
            $equipo->user_edit_id = auth()->id();
            $equipo->save();
            
            return response()->json(['success' => true, 'message' => 'Equipo eliminado'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ----------------------------------------------------
    // MÉTODOS PARA LA BITÁCORA DE MANTENIMIENTO
    // ----------------------------------------------------

    public function getBitacora($id)
    {
        try {
            // Buscamos los mantenimientos de un equipo en específico
            $bitacoras = BitacoraMantenimiento::where('equipo_id', $id)
                            ->where('status', 1)
                            ->orderBy('fecha_servicio', 'desc')
                            ->get();
                            
            return response()->json(['success' => true, 'data' => $bitacoras], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeBitacora(Request $request, $id)
    {
        $request->validate([
            'tipo_servicio' => 'required|string',
            'tecnico_asignado' => 'required|string',
            'fecha_servicio' => 'required|date',
            'trabajo_realizado' => 'required|string'
        ]);

        try {
            $bitacora = BitacoraMantenimiento::create([
                'equipo_id' => $id,
                'tipo_servicio' => $request->tipo_servicio,
                'tecnico_asignado' => $request->tecnico_asignado,
                'fecha_servicio' => $request->fecha_servicio,
                'trabajo_realizado' => $request->trabajo_realizado,
                'user_create_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true, 
                'data' => $bitacora, 
                'message' => 'Servicio registrado correctamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}