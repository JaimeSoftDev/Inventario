<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $table = 'movimientos_stock';

    public const TIPO_COMPRA = 'compra';

    public const TIPO_CONSUMO = 'consumo';

    public const TIPO_TRANSFERENCIA = 'transferencia';

    public const TIPO_CORRECCION = 'correccion';

    public const TIPO_APERTURA = 'apertura';

    protected $fillable = [
        'producto_id',
        'entrada_stock_id',
        'tipo',
        'cantidad',
        'ubicacion_origen_id',
        'ubicacion_destino_id',
        'usuario_registrador_id',
        'usuario_atribuido_id',
        'precio_unitario',
        'nota',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'precio_unitario' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function entradaStock(): BelongsTo
    {
        return $this->belongsTo(EntradaStock::class);
    }

    public function ubicacionOrigen(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino_id');
    }

    /**
     * Usuario que ejecutó físicamente la acción (proviene de la sesión/token activo).
     */
    public function usuarioRegistrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registrador_id');
    }

    /**
     * Usuario del hogar al que se atribuye el movimiento (elegible libremente).
     */
    public function usuarioAtribuido(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_atribuido_id');
    }
}
