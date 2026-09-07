<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CamaraCctv extends Model
{
    protected $table = 'camaras_cctv';
    
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_edited';

    protected $fillable = [
        'nombre_camara',
        'ubicacion',
        'stream_url',
        'estatus_red',
        'visible_en_caseta',
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