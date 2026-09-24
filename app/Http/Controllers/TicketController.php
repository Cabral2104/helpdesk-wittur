<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\TicketHistorial;

class TicketController
{
    /**
     * GET: Lista todos los tickets con sus relaciones (Paginación y Búsqueda).
     */
    public function index(Request $request)
    {
        try {
            $usuario = auth()->user();
            
            $sortBy = $request->query('sort_by', 'date_created');
            $sortOrder = $request->query('sort_order', 'desc');
            $search = $request->query('search', '');
            $categoriaId = $request->query('categoria_id', '');
            
            $allowedSorts = ['id', 'date_created', 'prioridad', 'estatus'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'date_created';
            }
            $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

            $query = Ticket::with([
                'usuarioReporta', 'equipo', 'categoria', 'tecnicoAsignado',
                'historial' => function($q) {
                    $q->orderBy('date_created', 'desc');
                }
            ]);

            // Filtrar por usuario si no es Administrador
            if ($usuario->rol !== 'Administrador') {
                $query->where('usuario_reporta_id', $usuario->id);
            }

            // Aplicar búsqueda por texto (ID o Descripción)
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('id', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion_falla', 'LIKE', "%{$search}%");
                });
            }

            // Aplicar filtro por categoría
            if (!empty($categoriaId)) {
                $query->where('categoria_id', $categoriaId);
            }

            // Aplicamos el ordenamiento dinámico y la paginación
            $tickets = $query->orderBy($sortBy, $sortOrder)->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $tickets->items(),
                'meta' => [
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'total' => $tickets->total()
                ]
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
        $request->validate([
            'titulo' => 'required|string|max:255', 
            'descripcion' => 'required|string',    
            'prioridad' => 'required|string|in:Baja,Media,Alta,Crítica',
            'categoria_id' => 'required|integer|exists:categorias_incidencias,id', 
            'equipo_id' => 'nullable|integer|exists:equipos_inventario,id' 
        ]);

        try {
            DB::beginTransaction();

            $folioGenerado = 'TKT-' . date('Ymd-Hi') . '-' . rand(10, 99);

            $ticket = Ticket::create([
                'folio' => $folioGenerado,
                'usuario_reporta_id' => auth()->id(), 
                'equipo_id' => $request->equipo_id ?? null,
                'categoria_id' => $request->categoria_id, 
                'prioridad' => $request->prioridad,
                'estatus' => 'Abierto', 
                'descripcion_falla' => $request->titulo . " - " . $request->descripcion,
                'user_create_id' => auth()->id() 
            ]);

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
     * PUT/PATCH: Actualiza un ticket.
     */
    public function update(Request $request, string $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket no encontrado'], 404);
        }

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

            $ticket->update($request->only(['estatus', 'tecnico_asignado_id', 'prioridad', 'descripcion_falla']));
            $ticket->user_edit_id = auth()->id();
            $ticket->save();

            if ($request->has('descripcion_falla') && !$cambioEstatus) {
                 TicketHistorial::create([
                    'ticket_id' => $ticket->id,
                    'estatus_anterior' => $estatusAnterior,
                    'estatus_nuevo' => $estatusAnterior,
                    'comentario_cambio' => 'El usuario actualizó la descripción del reporte.',
                    'user_create_id' => auth()->id() 
                ]);
            }

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

            $ticket->status = 0; 
            $ticket->user_edit_id = auth()->id(); 
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

    public function getCategorias()
    {
        try {
            $categorias = \Illuminate\Support\Facades\DB::table('categorias_incidencias')
                ->where('status', 1)
                ->select('id', 'nombre')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $categorias
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}