import apiClient, { esErrorDeRed } from '@/api/client'
import { db } from '@/db/dexie'

const ENDPOINTS = {
  compra: '/movimientos/compra',
  consumo: '/movimientos/consumo',
  correccion: '/movimientos/correccion',
}

/**
 * Envía un movimiento de stock (compra/consumo/corrección) al backend.
 * Si no hay conexión, o la petición falla por un error de red (no por una
 * regla de negocio como 422/403), se encola en Dexie para reintentar más
 * tarde. Los errores de negocio se propagan tal cual para que el formulario
 * los muestre al usuario.
 */
export function useMovimientoStock() {
  async function registrar(tipo, payload) {
    if (!navigator.onLine) {
      const colaId = await encolar(tipo, payload)
      return { encolado: true, colaId }
    }

    try {
      const { data } = await apiClient.post(ENDPOINTS[tipo], payload)
      return { encolado: false, data }
    } catch (error) {
      if (esErrorDeRed(error)) {
        const colaId = await encolar(tipo, payload)
        return { encolado: true, colaId }
      }

      // Error de negocio (422 stock insuficiente, 403 atribución no
      // autorizada, errores de validación): no se encola, se propaga.
      throw error
    }
  }

  async function encolar(tipo, payload) {
    return db.cola_movimientos.add({
      tipo,
      payload,
      estado: 'pendiente',
      creado_en: Date.now(),
      error_mensaje: null,
    })
  }

  /**
   * Deshace un consumo recién registrado.
   *
   * - Si aún estaba en la cola local, basta con sacarlo de ella: nunca
   *   llegó a existir para el resto del hogar.
   * - Si ya se confirmó, no se borra el histórico (rompería la trazabilidad
   *   que justifica todo el modelo): se devuelve la cantidad a cada lote del
   *   que salió mediante una corrección, atribuida a la misma persona.
   */
  async function deshacerConsumo(resultado) {
    if (resultado?.colaId) {
      await db.cola_movimientos.delete(resultado.colaId)
      return
    }

    const movimientos = resultado?.data?.data ?? []

    for (const movimiento of movimientos) {
      if (!movimiento.entrada_stock_id) continue

      const { data } = await apiClient.get(`/entradas-stock/${movimiento.entrada_stock_id}`)
      const restante = Number(data.data.cantidad_restante ?? 0)

      await apiClient.post(ENDPOINTS.correccion, {
        entrada_stock_id: movimiento.entrada_stock_id,
        cantidad_nueva: Number((restante + Number(movimiento.cantidad)).toFixed(3)),
        usuario_atribuido_id: movimiento.atribuido_a?.id,
        nota: 'Consumo deshecho',
      })
    }
  }

  return {
    registrarCompra: (payload) => registrar('compra', payload),
    registrarConsumo: (payload) => registrar('consumo', payload),
    registrarCorreccion: (payload) => registrar('correccion', payload),
    deshacerConsumo,
  }
}

export { ENDPOINTS }
