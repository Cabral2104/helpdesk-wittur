<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketHistorial extends Model
{
    protected $table = 'tickets_historial';
    
    const CREATED_AT = 'date_created';
    const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id', 
        'estatus_anterior', 
        'estatus_nuevo', 
        'comentario_cambio', 
        'user_create_id'
    ];
}