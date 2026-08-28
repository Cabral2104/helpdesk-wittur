<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';
    
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_edited';

    protected $fillable = [
        'folio',
        'usuario_reporta_id',
        'equipo_id',
        'categoria_id',
        'tecnico_asignado_id',
        'prioridad',
        'estatus',
        'descripcion_falla',
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

    // --- RELACIONES (BelongsTo) ---

    public function usuarioReporta()
    {
        return $this->belongsTo(Usuario::class, 'usuario_reporta_id');
    }

    public function equipo()
    {
        return $this->belongsTo(EquipoInventario::class, 'equipo_id');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaIncidencia::class, 'categoria_id');
    }

    public function tecnicoAsignado()
    {
        return $this->belongsTo(Usuario::class, 'tecnico_asignado_id');
    }

    // --- RELACIONES (HasMany) ---

    public function comentarios()
    {
        return $this->hasMany(TicketComentario::class, 'ticket_id');
    }

    public function adjuntos()
    {
        return $this->hasMany(TicketAdjunto::class, 'ticket_id');
    }

    public function historial()
    {
        return $this->hasMany(TicketHistorial::class, 'ticket_id');
    }
}