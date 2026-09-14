import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'

/**
 * Catálogos auxiliares (ubicaciones, unidades de medida, categorías) usados
 * en los formularios. Son listas pequeñas; se cargan una vez por sesión.
 */
export const useCatalogosStore = defineStore('catalogos', () => {
  const ubicaciones = ref([])
  const unidadesMedida = ref([])
  const categorias = ref([])

  async function cargarUbicaciones() {
    const { data } = await apiClient.get('/ubicaciones')
    ubicaciones.value = data.data
  }

  async function cargarUnidadesMedida() {
    const { data } = await apiClient.get('/unidades-medida')
    unidadesMedida.value = data.data
  }

  async function cargarCategorias() {
    const { data } = await apiClient.get('/categorias')
    categorias.value = data.data
  }

  async function cargarTodos() {
    await Promise.allSettled([cargarUbicaciones(), cargarUnidadesMedida(), cargarCategorias()])
  }

  return {
    ubicaciones,
    unidadesMedida,
    categorias,
    cargarUbicaciones,
    cargarUnidadesMedida,
    cargarCategorias,
    cargarTodos,
  }
})
