import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import { db } from '@/db/dexie'

/**
 * Catálogo de productos con su stock agregado. Estrategia
 * stale-while-revalidate: se muestra primero lo que haya en caché (si lo
 * hay) y, en paralelo, se refresca desde la red.
 */
export const useProductosStore = defineStore('productos', () => {
  const productos = ref([])
  const cargando = ref(false)
  const error = ref(null)

  async function cargarDesdeCache() {
    productos.value = await db.productos_cache.toArray()
  }

  async function cargar() {
    cargando.value = true
    error.value = null

    // stale: pintamos lo cacheado de inmediato mientras llega la red.
    await cargarDesdeCache()

    try {
      const { data } = await apiClient.get('/productos')
      // Se usa data.data (plano) en vez de productos.value: al asignarlo al
      // ref, Vue lo envuelve en un Proxy reactivo que IndexedDB no puede
      // clonar (DataCloneError).
      productos.value = data.data
      await db.productos_cache.clear()
      await db.productos_cache.bulkPut(data.data)
    } catch (e) {
      if (productos.value.length === 0) {
        error.value = 'No se pudo cargar el catálogo de productos y no hay datos en caché.'
      }
    } finally {
      cargando.value = false
    }
  }

  async function crear(datos) {
    const { data } = await apiClient.post('/productos', datos)
    productos.value.push(data.data)
    await db.productos_cache.put(data.data)
    return data.data
  }

  return { productos, cargando, error, cargar, cargarDesdeCache, crear }
})
