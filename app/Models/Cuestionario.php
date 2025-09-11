<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuestionario extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'nombre_corto',
        'descripcion',
    ];

    /**
     * Generar automáticamente el nombre de la tabla
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cuestionario) {
            $cuestionario->tabla = $cuestionario->nombre_corto . '_data';
        });

    }

    /**
     * Relación uno a muchos con variables
     */
    public function variables(): HasMany
    {
        return $this->hasMany(Variable::class);
    }
}
