<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Ticket;
use App\Models\ReporteSeguridad;
use App\Models\CamaraCctv;
use App\Models\Usuario;

class ReporteController
{
    public function exportar(Request $request)
    {
        try {
            $modulo = $request->query('modulo');
            $formato = $request->query('formato');
            
            $inicio = Carbon::parse($request->query('fecha_inicio'))->startOfDay()->format('Y-m-d H:i:s');
            $fin = Carbon::parse($request->query('fecha_fin'))->endOfDay()->format('Y-m-d H:i:s');

            $datos = [];
            
            $mapUsuarios = Usuario::pluck('nombre_completo', 'id')->toArray();
            $mapDeptos = DB::table('departamentos')->pluck('nombre', 'id')->toArray();

            if ($modulo === 'seguridad' || $modulo === 'todos') {
                $datos['seguridad'] = ReporteSeguridad::with(['camara'])
                    ->whereBetween('date_created', [$inicio, $fin])
                    ->get();
            }
            if ($modulo === 'tickets' || $modulo === 'todos') {
                $datos['tickets'] = Ticket::whereBetween('date_created', [$inicio, $fin])->get();
            }
            if ($modulo === 'cctv' || $modulo === 'todos') {
                $datos['cctv'] = CamaraCctv::all(); 
            }
            if ($modulo === 'usuarios' || $modulo === 'todos') {
                $datos['usuarios'] = Usuario::all(); 
            }
            if ($modulo === 'inventario' || $modulo === 'todos') {
                $datos['inventario'] = DB::table('equipos_inventario')->get(); 
            }

            if ($formato === 'json') {
                return response()->json(['periodo' => "$inicio al $fin", 'data' => $datos], 200);
            }

            if ($formato === 'excel') {
                return $this->generarCSV($datos, $modulo, $mapUsuarios, $mapDeptos);
            }

            if ($formato === 'pdf') {
                $pdf = Pdf::loadView('reportes.pdf', [
                    'datos' => $datos, 
                    'modulo' => $modulo, 
                    'inicio' => $request->query('fecha_inicio'), 
                    'fin' => $request->query('fecha_fin'),
                    'mapUsuarios' => $mapUsuarios,
                    'mapDeptos' => $mapDeptos
                ]);
                $pdf->setPaper('A4', 'landscape'); 
                return $pdf->download("Reporte_Wittur_{$modulo}.pdf");
            }
            
        } catch (\Exception $e) {
            Log::error('Error exportando reporte: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generarCSV($datos, $modulo, $mapUsuarios, $mapDeptos)
    {
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=Reporte_Wittur_{$modulo}.csv",
            "Pragma"              => "no-cache"
        ];

        $callback = function() use($datos, $mapUsuarios, $mapDeptos) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); 

            if (isset($datos['seguridad'])) {
                fputcsv($file, ['--- BITACORA DE SEGURIDAD ---']);
                fputcsv($file, ['ID', 'Fecha Incidente', 'Camara Involucrada', 'Reportado Por', 'Incidente', 'Estatus', 'Descripcion']);
                foreach ($datos['seguridad'] as $row) {
                    $camaraStr = $row->camara ? ($row->camara->nombre_camara . ' - ' . $row->camara->ubicacion) : 'ID: ' . $row->camara_id;
                    // CORREGIDO: Usando usuario_reporta_id
                    $guardiaStr = $mapUsuarios[$row->usuario_reporta_id] ?? 'Desconocido';
                    fputcsv($file, [$row->id, $row->fecha_incidente, $camaraStr, $guardiaStr, $row->tipo_incidente, $row->estatus, $row->descripcion]);
                }
                fputcsv($file, []);
            }

            if (isset($datos['tickets'])) {
                fputcsv($file, ['--- TICKETS DE SOPORTE TI ---']);
                fputcsv($file, ['Folio', 'Fecha Creacion', 'Reportado Por', 'Falla / Descripcion', 'Prioridad', 'Estatus']);
                foreach ($datos['tickets'] as $row) {
                    $usuarioStr = $mapUsuarios[$row->usuario_reporta_id] ?? 'Desconocido';
                    fputcsv($file, [$row->folio, $row->date_created, $usuarioStr, $row->descripcion_falla, $row->prioridad, $row->estatus]);
                }
                fputcsv($file, []);
            }
            
            if (isset($datos['cctv'])) {
                fputcsv($file, ['--- PADRON DE CAMARAS CCTV ---']);
                fputcsv($file, ['ID', 'Direccion IP', 'Nombre', 'Ubicacion', 'Numero de Serie', 'Estatus Red']);
                foreach ($datos['cctv'] as $row) {
                    // El "\t" fuerza a Excel a tratar el N/S como texto
                    $numeroSerieCctv = $row->numero_serie ? "\t" . $row->numero_serie : 'N/D';
                    fputcsv($file, [$row->id, $row->ip_asignada, $row->nombre_camara, $row->ubicacion, $numeroSerieCctv, $row->estatus_red ? 'Online' : 'Offline']);
                }
                fputcsv($file, []);
            }

            if (isset($datos['inventario'])) {
                fputcsv($file, ['--- INVENTARIO DE EQUIPOS ---']);
                fputcsv($file, ['ID', 'Codigo QR', 'Nombre en Red', 'Tipo', 'Marca/Modelo', 'Numero de Serie', 'Ubicacion', 'Usuario Asignado', 'Sistema Operativo']);
                foreach ($datos['inventario'] as $row) {
                    // Mismo truco para el inventario de TI
                    $numeroSerieInv = $row->numero_serie ? "\t" . $row->numero_serie : 'N/D';
                    
                    fputcsv($file, [
                        $row->id, 
                        $row->codigo_qr,
                        $row->nombre_red ?? 'N/D', 
                        $row->tipo_dispositivo ?? 'N/D', 
                        $row->marca_modelo ?? 'N/D', 
                        $numeroSerieInv,
                        $row->ubicacion ?? 'N/D',
                        $row->usuario_asignado ?? 'N/D',
                        $row->sistema_operativo ?? 'N/D'
                    ]);
                }
                fputcsv($file, []);
            }

            if (isset($datos['usuarios'])) {
                fputcsv($file, ['--- USUARIOS DEL SISTEMA ---']);
                fputcsv($file, ['Numero Nomina', 'Nombre Completo', 'Rol', 'Departamento']);
                foreach ($datos['usuarios'] as $row) {
                    $deptoStr = $mapDeptos[$row->departamento_id] ?? 'Desconocido';
                    // CORREGIDO: numero_nomina
                    fputcsv($file, [$row->numero_nomina ?? 'N/D', $row->nombre_completo, $row->rol, $deptoStr]);
                }
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}