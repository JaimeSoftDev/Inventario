import { ref } from 'vue'
import apiClient, { esErrorDeRed } from '@/api/client'
import { db } from '@/db/dexie'
import { ENDPOINTS } from '@/composables/useMovimientoStock'

/** Número de ítems pendientes de sincronizar, para mostrar en la UI. */
export const pendientesEnCola = ref(0)
export const sincronizando = ref(false)

async function actualizarContador() {
  pendientesEnCola.value = await db.cola_movimientos.where('estado').equals('pendiente').count()
}

/**
 * Recorre la cola de movimientos pendientes y los reenvía al backend en el
 * orden en que se crearon (para no alterar el orden real de los consumos).
 *
 * - Fallo de red (backend inalcanzable): el ítem se deja "pendiente" y se
 *   reintentará en la siguiente sincronización.
 * - Fallo de negocio (422/403): el contexto pudo cambiar mientras el
 *   dispositivo estaba offline (p. ej. ya no queda stock), así que NO se
 *   reintenta automáticamente; se marca como "error" para que el usuario lo
 *   revise.
 */
export async function sincronizar() {
  if (!navigator.onLine || sincronizando.value) {
    return
  }

  sincronizando.value = true

  try {
    const pendientes = await db.cola_movimientos
      .where('estado')
      .equals('pendiente')
      .sortBy('creado_en')

    for (const item of pendientes) {
      try {
        await apiClient.post(ENDPOINTS[item.tipo], item.payload)
        await db.cola_movimientos.delete(item.id)
      } catch (error) {
        if (esErrorDeRed(error)) {
          // Seguimos offline (o el backend cayó a mitad de la sync):
          // dejamos de intentar el resto y reintentamos todo en el
          // próximo evento "online".
          break
        }

        await db.cola_movimientos.update(item.id, {
          estado: 'error',
          error_mensaje: error.response?.data?.message ?? 'Error al sincronizar',
        })
      }
    }
  } finally {
    sincronizando.value = false
    await actualizarContador()
  }
}

let iniciado = false

/** Arranca el listener de reconexión. Debe llamarse una sola vez al boot de la app. */
export function iniciarSincronizador() {
  if (iniciado) return
  iniciado = true

  actualizarContador()
  window.addEventListener('online', sincronizar)

  if (navigator.onLine) {
    sincronizar()
  }
}
