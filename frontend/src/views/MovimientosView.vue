<script setup>
import { liveQuery } from 'dexie'
import { onMounted, onUnmounted, ref } from 'vue'
import apiClient from '@/api/client'
import { db } from '@/db/dexie'

const movimientos = ref([])
const cargando = ref(true)
const error = ref(null)
const colaLocal = ref([])
let suscripcion = null

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    const { data } = await apiClient.get('/movimientos')
    movimientos.value = data.data
  } catch (e) {
    error.value = 'No se pudo cargar el historial (¿sin conexión?).'
  } finally {
    cargando.value = false
  }
}

async function descartarError(id) {
  await db.cola_movimientos.delete(id)
}

onMounted(() => {
  cargar()

  // Se actualiza en vivo según cambia la cola offline (pendientes/errores).
  suscripcion = liveQuery(() => db.cola_movimientos.toArray()).subscribe({
    next: (valor) => (colaLocal.value = valor),
  })
})

onUnmounted(() => {
  suscripcion?.unsubscribe()
})
</script>

<template>
  <div>
    <h1>Movimientos</h1>

    <section v-if="colaLocal.length" class="card cola-offline">
      <h2>Pendientes de sincronizar</h2>
      <ul>
        <li v-for="item in colaLocal" :key="item.id">
          {{ item.tipo }} · cantidad {{ item.payload.cantidad }}
          <strong v-if="item.estado === 'error'" class="mensaje-error">
            — error: {{ item.error_mensaje }}
          </strong>
          <span v-else class="mensaje-info">— pendiente de red</span>
          <button v-if="item.estado === 'error'" type="button" @click="descartarError(item.id)">
            Descartar
          </button>
        </li>
      </ul>
    </section>

    <p v-if="cargando" class="mensaje-info">Cargando…</p>
    <p v-if="error" class="mensaje-error">{{ error }}</p>

    <ul class="lista-movimientos">
      <li v-for="mov in movimientos" :key="mov.id" class="card">
        <div>
          <strong>{{ mov.tipo }}</strong> · {{ mov.cantidad }} · {{ mov.producto?.nombre }}
        </div>
        <div class="mensaje-info">
          Registrado por {{ mov.registrado_por?.nombre }}, atribuido a {{ mov.atribuido_a?.nombre }}
        </div>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.lista-movimientos {
  list-style: none;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.cola-offline {
  margin-bottom: 1rem;
  border-color: #e0a83e;
}

.cola-offline ul {
  list-style: none;
  padding: 0;
}
</style>
