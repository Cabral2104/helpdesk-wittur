<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\TicketHistorial;

class TicketController
{
    /**
     * GET: Lista todos los tickets con sus relaciones.
     */
    public function index()
    {
        try {
            // 1. Identificamos quién está haciendo la petición
            $usuario = auth()->user();

            // 2. Preparamos la consulta base (Eager Loading)
            $query = Ticket::with([
                'usuarioReporta', 
                'equipo', 
                'categoria', 
                'tecnicoAsignado',
                'historial' => function($q) {
                    $q->orderBy('date_created', 'desc');
                }
            ]);

            // 3. REGLA DE NEGOCIO: Si NO es administrador, filtramos solo sus tickets
            if ($usuario->rol !== 'Administrador') {
                $query->where('usuario_reporta_id', $usuario->id);
            }

            // 4. Traemos los resultados ordenados por los más recientes
            $tickets = $query->orderBy('id', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $tickets
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tickets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST: Crea un nuevo ticket y su registro de historial.
     */
    public function store(Request $request)
    {
        // 1. Adaptamos la validación a lo que manda React
        $request->validate([
            'titulo' => 'required|string|max:255', // El asunto breve del frontend
            'descripcion' => 'required|string',     // La descripción del frontend
            'prioridad' => 'required|string|in:Baja,Media,Alta,Crítica',
            'categoria_incidencia_id' => 'required|integer|exists:categorias_incidencias,id',
            'equipo_id' => 'nullable|integer|exists:equipos_inventario,id' // Opcional por ahora
        ]);

        try {
            DB::beginTransaction();

            // 2. Auto-generamos un Folio único (Ej. TKT-20260828-1543)
            $folioGenerado = 'TKT-' . date('Ymd-Hi') . '-' . rand(10, 99);

            // 3. Crear el Ticket
            $ticket = Ticket::create([
                'folio' => $folioGenerado,
                'usuario_reporta_id' => auth()->id(), // Tomamos el ID del token Sanctum de forma segura
                'equipo_id' => $request->equipo_id ?? null,
                'categoria_id' => $request->categoria_incidencia_id,
                'prioridad' => $request->prioridad,
                'estatus' => 'Abierto', 
                // Unimos el título y descripción del frontend para guardarlo en tu columna de BD
                'descripcion_falla' => $request->titulo . " - " . $request->descripcion,
                'user_create_id' => auth()->id() 
            ]);

            // 4. Registrar automáticamente el evento en el Historial
            TicketHistorial::create([
                'ticket_id' => $ticket->id,
                'estatus_anterior' => 'Abierto',
                'estatus_nuevo' => 'Abierto',
                'comentario_cambio' => 'Ticket creado por el usuario en el portal.',
                'user_create_id' => auth()->id() 
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $ticket->load(['usuarioReporta', 'equipo', 'categoria']),
                'message' => 'Ticket creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error crítico al crear el ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: Muestra un ticket específico con todo su detalle e historial.
     */
    public function show(string $id)
    {
        try {
            // Buscamos el ticket por ID e incluimos su historial ordenado
            $ticket = Ticket::with(['usuarioReporta', 'equipo', 'categoria', 'tecnicoAsignado'])
                            ->with(['historial' => function($query) {
                                $query->orderBy('date_created', 'desc');
                            }])
                            ->find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $ticket
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT/PATCH: Actualiza un ticket (asignar técnico, estatus o corregir descripción).
     */
    public function update(Request $request, string $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket no encontrado'], 404);
        }

        // Validación dinámica: aceptamos estatus/técnicos (Admin) o descripcion_falla (Usuario)
        $request->validate([
            'estatus' => 'sometimes|required|string|in:Abierto,En_Progreso,Esperando_Piezas,Resuelto,Cerrado',
            'tecnico_asignado_id' => 'sometimes|required|integer|exists:usuarios,id',
            'comentario_cambio' => 'nullable|string',
            'descripcion_falla' => 'sometimes|required|string'
        ]);

        try {
            DB::beginTransaction();

            $estatusAnterior = $ticket->estatus;
            $cambioEstatus = $request->has('estatus') && $request->estatus !== $estatusAnterior;

            // Actualizamos los campos que vengan en la petición
            $ticket->update($request->only(['estatus', 'tecnico_asignado_id', 'prioridad', 'descripcion_falla']));
            $ticket->user_edit_id = auth()->id();
            $ticket->save();

            // Si el usuario normal modificó la descripción, dejamos registro en el historial
            if ($request->has('descripcion_falla') && !$cambioEstatus) {
                 TicketHistorial::create([
                    'ticket_id' => $ticket->id,
                    'estatus_anterior' => $estatusAnterior,
                    'estatus_nuevo' => $estatusAnterior,
                    'comentario_cambio' => 'El usuario actualizó la descripción del reporte.',
                    'user_create_id' => auth()->id() 
                ]);
            }

            // Si el admin cambió el estatus, registramos ese cambio
            if ($cambioEstatus) {
                $comentarioAutomatico = $request->comentario_cambio ?? "El sistema registró un cambio de estatus de '{$estatusAnterior}' a '{$request->estatus}'.";
                TicketHistorial::create([
                    'ticket_id' => $ticket->id,
                    'estatus_anterior' => $estatusAnterior,
                    'estatus_nuevo' => $request->estatus,
                    'comentario_cambio' => $comentarioAutomatico,
                    'user_create_id' => auth()->id() 
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $ticket,
                'message' => 'Ticket actualizado correctamente'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE: Borrado lógico del ticket.
     */
    public function destroy(string $id)
    {
        try {
            $ticket = Ticket::find($id);

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket no encontrado'], 404);
            }

            // Aplicamos el borrado lógico que definimos en el Modelo
            $ticket->status = 0; 
            $ticket->user_edit_id = auth()->id(); // Utilizamos el ID del usuario autenticado
            $ticket->save();

            return response()->json([
                'success' => true,
                'message' => 'Ticket eliminado (borrado lógico) correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el ticket: ' . $e->getMessage()
            ], 500);
        }
    }
}