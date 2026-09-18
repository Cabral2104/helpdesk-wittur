<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Wittur ICT</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        h1 { margin: 0; color: #0056b3; font-size: 18px; text-transform: uppercase; }
        h2 { font-size: 12px; background-color: #f1f5f9; padding: 6px; border-left: 4px solid #0056b3; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; word-wrap: break-word; }
        th { background-color: #e2e8f0; font-weight: bold; text-transform: uppercase; font-size: 8px; }
        
        .col-sm { width: 8%; }
        .col-md { width: 15%; }
        .col-lg { width: 25%; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reporte Operativo Wittur ICT</h1>
        <p><strong>Módulo:</strong> {{ strtoupper($modulo) }} | <strong>Periodo:</strong> {{ $inicio }} al {{ $fin }}</p>
    </div>

    @if(isset($datos['seguridad']))
        <h2>Bitácora de Seguridad (Novedades en Caseta)</h2>
        <table>
            <thead>
                <tr>
                    <th class="col-md">Fecha Incidente</th>
                    <th class="col-md">Cámara</th>
                    <th class="col-md">Reportado Por</th>
                    <th class="col-md">Incidente</th>
                    <th class="col-lg">Descripción</th>
                    <th class="col-sm">Estatus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($datos['seguridad'] as $rep)
                    <tr>
                        <td>{{ $rep->fecha_incidente }}</td>
                        <td>{{ $rep->camara ? $rep->camara->nombre_camara : 'ID: ' . $rep->camara_id }}</td>
                        <td>{{ $mapUsuarios[$rep->usuario_reporta_id] ?? 'Desconocido' }}</td>
                        <td>{{ $rep->tipo_incidente }}</td>
                        <td>{{ $rep->descripcion }}</td>
                        <td>{{ str_replace('_', ' ', $rep->estatus) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;">No hay registros de seguridad.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if(isset($datos['tickets']))
        <h2>Tickets de Soporte TI</h2>
        <table>
            <thead>
                <tr>
                    <th class="col-md">Folio</th>
                    <th class="col-md">Fecha Creación</th>
                    <th class="col-md">Reportado Por</th>
                    <th class="col-lg">Falla / Descripción</th>
                    <th class="col-sm">Prioridad</th>
                    <th class="col-sm">Estatus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($datos['tickets'] as $ticket)
                    <tr>
                        <td>{{ $ticket->folio }}</td>
                        <td>{{ $ticket->date_created }}</td>
                        <td>{{ $mapUsuarios[$ticket->usuario_reporta_id] ?? 'Desconocido' }}</td>
                        <td>{{ $ticket->descripcion_falla }}</td>
                        <td>{{ $ticket->prioridad }}</td>
                        <td>{{ str_replace('_', ' ', $ticket->estatus) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;">No hay tickets registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if(isset($datos['inventario']))
        <h2>Inventario de Equipos</h2>
        <table>
            <thead>
                <tr>
                    <th class="col-sm">ID</th>
                    <th class="col-md">Nombre en Red</th>
                    <th class="col-sm">Tipo</th>
                    <th class="col-lg">Marca / Modelo</th>
                    <th class="col-md">Número de Serie</th>
                    <th class="col-md">Usuario Asignado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($datos['inventario'] as $eq)
                    <tr>
                        <td>{{ $eq->id }}</td>
                        <td>{{ $eq->nombre_red ?? 'N/D' }}</td>
                        <td>{{ $eq->tipo_dispositivo ?? 'N/D' }}</td>
                        <td>{{ $eq->marca_modelo ?? 'N/D' }}</td>
                        <td>{{ $eq->numero_serie ?? 'N/D' }}</td>
                        <td>{{ $eq->usuario_asignado ?? 'N/D' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;">No hay equipos en el inventario.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if(isset($datos['cctv']))
        <h2>Padrón de Cámaras CCTV</h2>
        <table>
            <thead>
                <tr>
                    <th class="col-sm">ID</th>
                    <th class="col-md">Dirección IP</th>
                    <th class="col-md">Nombre</th>
                    <th class="col-md">Número de Serie (N/S)</th>
                    <th class="col-md">Ubicación</th>
                    <th class="col-sm">Conexión</th>
                </tr>
            </thead>
            <tbody>
                @forelse($datos['cctv'] as $cam)
                    <tr>
                        <td>{{ $cam->id }}</td>
                        <td>{{ $cam->ip_asignada }}</td>
                        <td>{{ $cam->nombre_camara }}</td>
                        <td>{{ $cam->numero_serie ?? 'N/D' }}</td>
                        <td>{{ $cam->ubicacion }}</td>
                        <td>{{ $cam->estatus_red ? 'Online' : 'Offline' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;">No hay cámaras registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if(isset($datos['usuarios']))
        <h2>Usuarios y Accesos</h2>
        <table>
            <thead>
                <tr>
                    <th class="col-md">Número de Nómina</th>
                    <th class="col-lg">Nombre Completo</th>
                    <th class="col-md">Rol (Acceso)</th>
                    <th class="col-md">Departamento</th>
                </tr>
            </thead>
            <tbody>
                @forelse($datos['usuarios'] as $usr)
                    <tr>
                        <td>{{ $usr->numero_nomina ?? 'N/D' }}</td>
                        <td>{{ $usr->nombre_completo }}</td>
                        <td>{{ $usr->rol }}</td>
                        <td>{{ $mapDeptos[$usr->departamento_id] ?? 'Desconocido' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;">No hay usuarios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

</body>
</html>