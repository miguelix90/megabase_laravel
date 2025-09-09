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
        'tabla',
    ];

    /**
     * Relación uno a muchos con variables
     */
    public function variables(): HasMany
    {
        return $this->hasMany(Variable::class);
    }
}
