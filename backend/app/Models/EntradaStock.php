<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EntradaStock extends Model
{
    use HasFactory;

    protected $table = 'entradas_stock';

    protected $fillable = [
        'producto_id',
        'ubicacion_id',
        'cantidad_restante',
        'fecha_compra',
        'fecha_caducidad',
        'precio_unitario',
        'abierto',
        'nota',
    ];

    protected $casts = [
        'cantidad_restante' => 'decimal:3',
        'precio_unitario' => 'decimal:2',
        'abierto' => 'boolean',
        'fecha_compra' => 'date',
        'fecha_caducidad' => 'date',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }

    /**
     * Movimiento de compra que originó el lote. Permite saber a quién se
     * atribuyó la compra sin duplicar el dato en la propia entrada.
     */
    public function movimientoCompra(): HasOne
    {
        return $this->hasOne(MovimientoStock::class)
            ->where('tipo', MovimientoStock::TIPO_COMPRA);
    }
}
