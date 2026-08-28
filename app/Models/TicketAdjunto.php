<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAdjunto extends Model
{
    protected $table = 'ticket_adjuntos';
    
    const CREATED_AT = 'date_created';
    const UPDATED_AT = null;

    protected $fillable = ['ticket_id', 'nombre_archivo', 'ruta_archivo', 'tipo_mime', 'status', 'user_create_id'];

    protected static function booted()
    {
        static::addGlobalScope('activos', function ($builder) {
            $builder->where('status', 1);
        });
    }
}