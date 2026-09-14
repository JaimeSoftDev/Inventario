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
      await encolar(tipo, payload)
      return { encolado: true }
    }

    try {
      const { data } = await apiClient.post(ENDPOINTS[tipo], payload)
      return { encolado: false, data }
    } catch (error) {
      if (esErrorDeRed(error)) {
        await encolar(tipo, payload)
        return { encolado: true }
      }

      // Error de negocio (422 stock insuficiente, 403 atribución no
      // autorizada, errores de validación): no se encola, se propaga.
      throw error
    }
  }

  async function encolar(tipo, payload) {
    await db.cola_movimientos.add({
      tipo,
      payload,
      estado: 'pendiente',
      creado_en: Date.now(),
      error_mensaje: null,
    })
  }

  return {
    registrarCompra: (payload) => registrar('compra', payload),
    registrarConsumo: (payload) => registrar('consumo', payload),
    registrarCorreccion: (payload) => registrar('correccion', payload),
  }
}

export { ENDPOINTS }
