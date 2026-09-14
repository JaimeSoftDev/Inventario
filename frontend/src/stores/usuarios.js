import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import { db } from '@/db/dexie'

/**
 * Usuarios del hogar. Se usa para poblar el selector "Registrar a nombre
 * de". Cambia poco, así que se sirve de caché local (Dexie) cuando no hay
 * red, y se refresca en segundo plano cuando sí la hay.
 */
export const useUsuariosStore = defineStore('usuarios', () => {
  const usuarios = ref([])
  const cargando = ref(false)

  async function cargarDesdeCache() {
    usuarios.value = await db.usuarios_cache.toArray()
  }

  async function cargar() {
    cargando.value = true
    try {
      const { data } = await apiClient.get('/usuarios')
      // Se usa data.data (plano) en vez de usuarios.value: al asignarlo al
      // ref, Vue lo envuelve en un Proxy reactivo que IndexedDB no puede
      // clonar (DataCloneError).
      usuarios.value = data.data
      await db.usuarios_cache.clear()
      await db.usuarios_cache.bulkPut(data.data)
    } catch (error) {
      // Sin red o backend caído: servimos lo último que tengamos cacheado.
      await cargarDesdeCache()
    } finally {
      cargando.value = false
    }
  }

  return { usuarios, cargando, cargar, cargarDesdeCache }
})
