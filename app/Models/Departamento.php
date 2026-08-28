<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    // 1. Especificar el nombre exacto de la tabla
    protected $table = 'departamentos';

    // 2. Mapear las columnas de auditoría personalizadas
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_edited';

    // 3. Definir los campos que se pueden llenar masivamente
    protected $fillable = [
        'nombre',
        'status',
        'user_create_id',
        'user_edit_id'
    ];

    // 4. Scope global para ignorar los registros inactivos (Borrado lógico)
    protected static function booted()
    {
        static::addGlobalScope('activos', function ($builder) {
            $builder->where('status', 1);
        });
    }
}