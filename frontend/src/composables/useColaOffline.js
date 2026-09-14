import { liveQuery } from 'dexie'
import { computed, onScopeDispose, ref } from 'vue'
import { db } from '@/db/dexie'

/**
 * Vista reactiva de la cola de escritura offline.
 *
 * La cola es la fuente de verdad de "lo que aún no está confirmado": el
 * listado la usa para marcar productos con movimientos en vuelo, el banner
 * para el contador y la pantalla de cola para resolver conflictos.
 */
export function useColaOffline() {
  const items = ref([])

  const suscripcion = liveQuery(() => db.cola_movimientos.orderBy('creado_en').toArray()).subscribe({
    next: (valor) => (items.value = valor),
  })

  onScopeDispose(() => suscripcion.unsubscribe())

  const pendientes = computed(() => items.value.filter((item) => item.estado !== 'error'))
  const conflictos = computed(() => items.value.filter((item) => item.estado === 'error'))

  /** Nº de movimientos en vuelo por producto, para el badge de cada fila. */
  const pendientesPorProducto = computed(() => {
    const mapa = {}
    for (const item of pendientes.value) {
      const id = item.payload?.producto_id
      if (id) mapa[id] = (mapa[id] ?? 0) + 1
    }
    return mapa
  })

  async function descartar(id) {
    await db.cola_movimientos.delete(id)
  }

  /**
   * Devuelve un conflicto a la cola de pendientes para que el sincronizador
   * lo reintente (tras corregirlo o al aceptar aplicarlo igualmente).
   */
  async function reencolar(id, cambios = {}) {
    const item = await db.cola_movimientos.get(id)
    if (!item) return

    await db.cola_movimientos.update(id, {
      estado: 'pendiente',
      error_mensaje: null,
      payload: { ...item.payload, ...cambios },
    })
  }

  return { items, pendientes, conflictos, pendientesPorProducto, descartar, reencolar }
}
