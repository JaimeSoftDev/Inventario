<script setup>
// registerType: 'prompt' -> no se activa la nueva versión sola a media
// acción del usuario; se le avisa y decide cuándo recargar.
import { useRegisterSW } from 'virtual:pwa-register/vue'

const { needRefresh, updateServiceWorker } = useRegisterSW({
  immediate: true,
})

function actualizar() {
  updateServiceWorker(true)
}

function descartar() {
  needRefresh.value = false
}
</script>

<template>
  <div v-if="needRefresh" class="toast-actualizacion" role="status">
    <span>Hay una nueva versión disponible.</span>
    <div class="toast-actualizacion__acciones">
      <button type="button" @click="actualizar">Actualizar</button>
      <button type="button" @click="descartar">Ahora no</button>
    </div>
  </div>
</template>

<style scoped>
.toast-actualizacion {
  position: fixed;
  bottom: 1rem;
  left: 50%;
  transform: translateX(-50%);
  background: #1f1f1f;
  color: #fff;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
  z-index: 1000;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.toast-actualizacion__acciones {
  display: flex;
  gap: 0.5rem;
}

.toast-actualizacion button {
  border: 1px solid #fff;
  background: transparent;
  color: #fff;
  border-radius: 0.25rem;
  padding: 0.25rem 0.75rem;
}
</style>
