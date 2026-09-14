<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { pendientesEnCola, sincronizando } from '@/composables/useSincronizador'

const enLinea = ref(navigator.onLine)

function actualizar() {
  enLinea.value = navigator.onLine
}

onMounted(() => {
  window.addEventListener('online', actualizar)
  window.addEventListener('offline', actualizar)
})

onUnmounted(() => {
  window.removeEventListener('online', actualizar)
  window.removeEventListener('offline', actualizar)
})
</script>

<template>
  <span class="indicador" :class="{ 'indicador--offline': !enLinea }">
    <span class="indicador__punto" />
    {{ enLinea ? 'En línea' : 'Sin conexión' }}
    <span v-if="pendientesEnCola > 0">
      · {{ pendientesEnCola }} pendiente{{ pendientesEnCola === 1 ? '' : 's' }}
      <span v-if="sincronizando">(sincronizando…)</span>
    </span>
  </span>
</template>

<style scoped>
.indicador {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.85rem;
  opacity: 0.8;
}

.indicador__punto {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background: #2e7d32;
}

.indicador--offline .indicador__punto {
  background: #b00020;
}
</style>
