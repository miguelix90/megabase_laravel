<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_unico',
        'grupo',
        'sexo',
        'adoptado',
        'fecha_nacimiento',
        'excluido',
        'motivo_exclusion',
        'hash',
        'observaciones',
    ];

    protected $casts = [
        'adoptado' => 'boolean',
        'excluido' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Validación del formato del código único
     * Formato: G01_A0001 (Letra, dos números, guion bajo, letra, cuatro números)
     */
    public static function validateCodigoUnico($codigo)
    {
        return preg_match('/^[A-Z]\d{2}_[A-Z]\d{4}$/', $codigo);
    }

    /**
     * Scope para participantes no excluidos
     */
    public function scopeNotExcluded($query)
    {
        return $query->where('excluido', false);
    }

    /**
     * Scope para participantes excluidos
     */
    public function scopeExcluded($query)
    {
        return $query->where('excluido', true);
    }
}
