<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variable extends Model
{
    use HasFactory;

    /**
     * Tipos de variables disponibles
     */
    const TIPOS = [
        'radio',
        'select',
        'date',
        'integer',
        'float',
        'varchar',
        'text'
    ];

    protected $fillable = [
        'cuestionario_id',
        'nombre',
        'etiqueta',
        'tipo',
        'valores',
    ];

    /**
     * Reglas de validación
     */
    public static function rules()
    {
        return [
            'cuestionario_id' => 'required|exists:cuestionarios,id',
            'nombre' => 'required|string|max:100|unique:variables,nombre',
            'etiqueta' => 'required|string|max:100',
            'tipo' => 'required|string|max:20|in:' . implode(',', self::TIPOS),
            'valores' => 'nullable|string',
        ];
    }

    /**
     * Obtener los tipos disponibles
     */
    public static function getTipos()
    {
        return self::TIPOS;
    }

    /**
     * Verificar si es un tipo válido
     */
    public static function esTipoValido($tipo)
    {
        return in_array($tipo, self::TIPOS);
    }

    /**
     * Relación muchos a uno con cuestionario
     */
    public function cuestionario(): BelongsTo
    {
        return $this->belongsTo(Cuestionario::class);
    }
}
