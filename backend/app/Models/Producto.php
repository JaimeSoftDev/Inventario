<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria_id',
        'unidad_medida_id',
        'ubicacion_por_defecto_id',
        'stock_minimo',
        'dias_caducidad_por_defecto',
        'precio_referencia',
        'notas',
    ];

    protected $casts = [
        'stock_minimo' => 'decimal:3',
        'precio_referencia' => 'decimal:2',
        'dias_caducidad_por_defecto' => 'integer',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function ubicacionPorDefecto(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_por_defecto_id');
    }

    public function entradasStock(): HasMany
    {
        return $this->hasMany(EntradaStock::class);
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }

    /**
     * Suma de cantidad_restante de todas las entradas de stock del producto.
     */
    public function stockActual(): string
    {
        return (string) $this->entradasStock()->sum('cantidad_restante');
    }
}
