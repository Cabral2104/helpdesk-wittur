<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoInventario extends Model
{
    protected $table = 'equipos_inventario';
    
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_edited';

    protected $fillable = [
        'codigo_qr',
        'tipo_dispositivo',
        'marca_modelo',
        'ubicacion',
        'ip_address',
        'status',
        'user_create_id',
        'user_edit_id'
    ];

    protected static function booted()
    {
        static::addGlobalScope('activos', function ($builder) {
            $builder->where('status', 1);
        });
    }
}