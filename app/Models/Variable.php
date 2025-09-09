<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variable extends Model
{
    use HasFactory;

    protected $fillable = [
        'cuestionario_id',
        'nombre',
        'etiqueta',
        'tipo',
        'valores',
    ];

    /**
     * Relación muchos a uno con cuestionario
     */
    public function cuestionario(): BelongsTo
    {
        return $this->belongsTo(Cuestionario::class);
    }
}
