<?php

namespace App\Services;

use App\Exceptions\AtribucionNoAutorizadaException;
use App\Exceptions\StockInsuficienteException;
use App\Models\EntradaStock;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use App\Repositories\Contracts\EntradaStockRepositoryInterface;
use App\Repositories\Contracts\MovimientoStockRepositoryInterface;
use App\Repositories\Criteria\EntradasDisponiblesFEFOCriteria;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta las reglas de negocio de compra/consumo de stock: validación de
 * permisos de atribución, aplicación de la política FEFO y consistencia
 * transaccional de las cantidades.
 */
class MovimientoStockService
{
    public function __construct(
        private readonly EntradaStockRepositoryInterface $entradaStockRepository,
        private readonly MovimientoStockRepositoryInterface $movimientoStockRepository,
    ) {}

    /**
     * Registra la compra de un producto: crea una nueva entrada de stock y
     * su movimiento correspondiente.
     *
     * @throws AtribucionNoAutorizadaException
     */
    public function registrarCompra(Producto $producto, array $datos, User $usuarioRegistrador): MovimientoStock
    {
        $this->validarAtribucion($usuarioRegistrador, (int) $datos['usuario_atribuido_id']);

        return DB::transaction(function () use ($producto, $datos, $usuarioRegistrador) {
            $entrada = $this->entradaStockRepository->crear([
                'producto_id' => $producto->id,
                'ubicacion_id' => $datos['ubicacion_id'],
                'cantidad_restante' => $datos['cantidad'],
                'fecha_compra' => $datos['fecha_compra'] ?? now()->toDateString(),
                'fecha_caducidad' => $datos['fecha_caducidad'] ?? null,
                'precio_unitario' => $datos['precio_unitario'] ?? null,
                'abierto' => $datos['abierto'] ?? false,
                'nota' => $datos['nota'] ?? null,
            ]);

            return $this->movimientoStockRepository->crear([
                'producto_id' => $producto->id,
                'entrada_stock_id' => $entrada->id,
                'tipo' => MovimientoStock::TIPO_COMPRA,
                'cantidad' => $datos['cantidad'],
                'ubicacion_destino_id' => $datos['ubicacion_id'],
                'usuario_registrador_id' => $usuarioRegistrador->id,
                'usuario_atribuido_id' => $datos['usuario_atribuido_id'],
                'precio_unitario' => $datos['precio_unitario'] ?? null,
                'nota' => $datos['nota'] ?? null,
            ]);
        });
    }

    /**
     * Registra el consumo de una cantidad de producto, repartiéndola entre
     * las entradas de stock disponibles según la política FEFO. Genera un
     * movimiento por cada entrada afectada, todo dentro de una única
     * transacción de base de datos.
     *
     * @return Collection<int, MovimientoStock>
     *
     * @throws AtribucionNoAutorizadaException
     * @throws StockInsuficienteException
     */
    public function registrarConsumo(Producto $producto, array $datos, User $usuarioRegistrador): Collection
    {
        $this->validarAtribucion($usuarioRegistrador, (int) $datos['usuario_atribuido_id']);

        $cantidadSolicitada = round((float) $datos['cantidad'], 3);

        return DB::transaction(function () use ($producto, $datos, $usuarioRegistrador, $cantidadSolicitada) {
            $entradas = $this->entradaStockRepository->porCriteriaBloqueando(
                new EntradasDisponiblesFEFOCriteria($producto->id)
            );

            $totalDisponible = round((float) $entradas->sum('cantidad_restante'), 3);

            if ($totalDisponible < $cantidadSolicitada) {
                throw new StockInsuficienteException($cantidadSolicitada, $totalDisponible);
            }

            $restante = $cantidadSolicitada;
            $movimientos = collect();

            /** @var EntradaStock $entrada */
            foreach ($entradas as $entrada) {
                if ($restante <= 0) {
                    break;
                }

                $disponibleEnEntrada = round((float) $entrada->cantidad_restante, 3);
                $cantidadATomar = min($restante, $disponibleEnEntrada);

                $entrada->cantidad_restante = round($disponibleEnEntrada - $cantidadATomar, 3);
                $this->entradaStockRepository->guardar($entrada);

                $movimientos->push($this->movimientoStockRepository->crear([
                    'producto_id' => $producto->id,
                    'entrada_stock_id' => $entrada->id,
                    'tipo' => MovimientoStock::TIPO_CONSUMO,
                    'cantidad' => $cantidadATomar,
                    'ubicacion_origen_id' => $entrada->ubicacion_id,
                    'usuario_registrador_id' => $usuarioRegistrador->id,
                    'usuario_atribuido_id' => $datos['usuario_atribuido_id'],
                    'nota' => $datos['nota'] ?? null,
                ]));

                $restante = round($restante - $cantidadATomar, 3);
            }

            return $movimientos;
        });
    }

    /**
     * Corrige manualmente la cantidad restante de una entrada de stock
     * concreta (p. ej. tras un recuento físico), dejando constancia del
     * ajuste como un movimiento de tipo "correccion".
     *
     * @throws AtribucionNoAutorizadaException
     */
    public function registrarCorreccion(EntradaStock $entrada, array $datos, User $usuarioRegistrador): MovimientoStock
    {
        $this->validarAtribucion($usuarioRegistrador, (int) $datos['usuario_atribuido_id']);

        return DB::transaction(function () use ($entrada, $datos, $usuarioRegistrador) {
            $entradaBloqueada = $this->entradaStockRepository->bloquearParaActualizar($entrada->id);

            $cantidadAnterior = round((float) $entradaBloqueada->cantidad_restante, 3);
            $cantidadNueva = round((float) $datos['cantidad_nueva'], 3);
            $delta = round($cantidadNueva - $cantidadAnterior, 3);

            $entradaBloqueada->cantidad_restante = $cantidadNueva;
            $this->entradaStockRepository->guardar($entradaBloqueada);

            return $this->movimientoStockRepository->crear([
                'producto_id' => $entradaBloqueada->producto_id,
                'entrada_stock_id' => $entradaBloqueada->id,
                'tipo' => MovimientoStock::TIPO_CORRECCION,
                'cantidad' => $delta,
                'usuario_registrador_id' => $usuarioRegistrador->id,
                'usuario_atribuido_id' => $datos['usuario_atribuido_id'],
                'nota' => $datos['nota'] ?? null,
            ]);
        });
    }

    /**
     * Un usuario siempre puede atribuirse movimientos a sí mismo. Atribuir a
     * otro usuario requiere el permiso puedeAtribuirAOtros().
     *
     * @throws AtribucionNoAutorizadaException
     */
    private function validarAtribucion(User $usuarioRegistrador, int $usuarioAtribuidoId): void
    {
        if ($usuarioAtribuidoId === $usuarioRegistrador->id) {
            return;
        }

        if (! $usuarioRegistrador->puedeAtribuirAOtros()) {
            throw new AtribucionNoAutorizadaException;
        }
    }
}
