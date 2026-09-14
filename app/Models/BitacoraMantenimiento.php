<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraMantenimiento extends Model
{
    protected $table = 'bitacora_mantenimientos';

    // Apagamos el UPDATED_AT porque en SQL solo creamos el campo date_created
    const CREATED_AT = 'date_created';
    const UPDATED_AT = null;

    protected $fillable = [
        'equipo_id',
        'tipo_servicio',
        'tecnico_asignado',
        'fecha_servicio',
        'trabajo_realizado',
        'status',
        'user_create_id'
    ];

    // Relación inversa para saber a qué equipo pertenece
    public function equipo()
    {
        return $this->belongsTo(EquipoInventario::class, 'equipo_id', 'id');
    }
}