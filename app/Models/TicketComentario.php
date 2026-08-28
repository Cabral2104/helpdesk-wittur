<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketComentario extends Model
{
    protected $table = 'ticket_comentarios';
    
    const CREATED_AT = 'date_created';
    const UPDATED_AT = null;

    protected $fillable = ['ticket_id', 'comentario', 'status', 'user_create_id'];

    protected static function booted()
    {
        static::addGlobalScope('activos', function ($builder) {
            $builder->where('status', 1);
        });
    }
}