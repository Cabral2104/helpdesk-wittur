<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaIncidencia extends Model
{
    protected $table = 'categorias_incidencias';
    
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_edited';

    protected $fillable = [
        'nombre',
        'sla_horas',
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