<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteSeguridad extends Model
{
    protected $table = 'reportes_seguridad';
    
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_edited';

    protected $fillable = [
        'camara_id',
        'usuario_reporta_id',
        'fecha_incidente',
        'tipo_incidente',
        'descripcion',
        'estatus'
    ];

    public function camara()
    {
        return $this->belongsTo(CamaraCctv::class, 'camara_id');
    }

    public function guardia()
    {
        return $this->belongsTo(Usuario::class, 'usuario_reporta_id');
    }
}