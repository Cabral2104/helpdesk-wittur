<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'usuarios';
    
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_edited';

    protected $fillable = [
        'numero_nomina',
        'nombre_completo',
        'departamento_id',
        'rol',
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

    // Relación: Un usuario pertenece a un departamento
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }
}