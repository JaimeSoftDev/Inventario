<script setup>
import { onMounted, ref } from 'vue'
import apiClient from '@/api/client'
import { db } from '@/db/dexie'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const stock = ref(null)
const cargando = ref(true)
const error = ref(null)
const desdeCache = ref(false)

async function cargarDesdeCache() {
  const productoId = Number(props.id)
  const entradas = await db.entradas_stock_cache.where('producto_id').equals(productoId).toArray()

  if (!entradas.length) {
    return false
  }

  desdeCache.value = true
  stock.value = {
    producto_id: productoId,
    stock_total: entradas.reduce((suma, e) => suma + Number(e.cantidad_restante), 0),
    entradas,
  }
  return true
}

async function cargar() {
  cargando.value = true
  error.value = null
  desdeCache.value = false

  try {
    const { data } = await apiClient.get(`/productos/${props.id}/stock`)
    stock.value = data
    // Cachea las entradas (planas, sin proxies de Vue) para poder mostrar
    // el stock sin conexión la próxima vez.
    await db.entradas_stock_cache.where('producto_id').equals(Number(props.id)).delete()
    await db.entradas_stock_cache.bulkPut(data.entradas)
  } catch (e) {
    const huboCache = await cargarDesdeCache()
    if (!huboCache) {
      error.value = 'No se pudo cargar el stock (¿sin conexión?) y no hay datos en caché.'
    }
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)
</script>

<template>
  <div>
    <h1>Stock del producto</h1>

    <p v-if="cargando" class="mensaje-info">Cargando…</p>
    <p v-if="error" class="mensaje-error">{{ error }}</p>
    <p v-if="desdeCache" class="mensaje-info">Mostrando datos guardados (sin conexión).</p>

    <template v-if="stock">
      <p><strong>Stock total: {{ stock.stock_total }}</strong></p>

      <ul class="lista-entradas">
        <li v-for="entrada in stock.entradas" :key="entrada.id" class="card">
          <div>{{ entrada.cantidad_restante }} · {{ entrada.ubicacion?.nombre }}</div>
          <div class="mensaje-info">
            Caduca: {{ entrada.fecha_caducidad ?? 'sin caducidad' }}
            <span v-if="entrada.abierto">· abierto</span>
          </div>
        </li>
      </ul>

      <p v-if="!stock.entradas.length" class="mensaje-info">Sin stock disponible.</p>
    </template>
  </div>
</template>

<style scoped>
.lista-entradas {
  list-style: none;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
</style>
