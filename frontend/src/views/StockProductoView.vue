<script setup>
import { onMounted, ref } from 'vue'
import apiClient from '@/api/client'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const stock = ref(null)
const cargando = ref(true)
const error = ref(null)

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    const { data } = await apiClient.get(`/productos/${props.id}/stock`)
    stock.value = data
  } catch (e) {
    error.value = 'No se pudo cargar el stock (¿sin conexión?).'
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
