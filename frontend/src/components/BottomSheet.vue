<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'

/**
 * Hoja inferior. Mantiene el contexto de la pantalla de debajo (se ve
 * atenuada) y deja todo el contenido al alcance del pulgar. Se puede cerrar
 * arrastrando el tirador hacia abajo, tocando fuera o con Escape.
 */
const props = defineProps({
  abierta: { type: Boolean, default: false },
  // A pantalla casi completa para formularios largos (alta de stock).
  alta: { type: Boolean, default: false },
})

const emit = defineEmits(['cerrar'])

const desplazamiento = ref(0)
let inicioY = 0
let arrastrando = false

function alPulsar(evento) {
  arrastrando = true
  inicioY = evento.clientY
}

function alMover(evento) {
  if (!arrastrando) return
  desplazamiento.value = Math.max(0, evento.clientY - inicioY)
}

function alSoltar() {
  if (!arrastrando) return
  arrastrando = false

  if (desplazamiento.value > 96) {
    emit('cerrar')
  }
  desplazamiento.value = 0
}

function alPulsarTecla(evento) {
  if (evento.key === 'Escape') emit('cerrar')
}

watch(
  () => props.abierta,
  (abierta) => {
    // Bloquea el scroll de fondo mientras la hoja está abierta.
    document.body.style.overflow = abierta ? 'hidden' : ''
    desplazamiento.value = 0

    if (abierta) {
      window.addEventListener('keydown', alPulsarTecla)
    } else {
      window.removeEventListener('keydown', alPulsarTecla)
    }
  },
)

onBeforeUnmount(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', alPulsarTecla)
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="abierta"
        class="fixed inset-0 z-40 bg-arena-900/35 backdrop-blur-[2px]"
        @click="emit('cerrar')"
      />
    </Transition>

    <Transition
      enter-active-class="transition duration-250 ease-out"
      enter-from-class="translate-y-full"
      leave-active-class="transition duration-200 ease-in"
      leave-to-class="translate-y-full"
    >
      <div
        v-if="abierta"
        class="fixed inset-x-0 bottom-0 z-50 mx-auto flex max-w-[460px] flex-col rounded-t-[28px] bg-fondo shadow-lg"
        :class="alta ? 'max-h-[94dvh] h-[94dvh]' : 'max-h-[88dvh]'"
        :style="{ transform: `translateY(${desplazamiento}px)` }"
        role="dialog"
        aria-modal="true"
      >
        <div
          class="flex shrink-0 cursor-grab justify-center pt-3 pb-1 active:cursor-grabbing"
          @pointerdown="alPulsar"
          @pointermove="alMover"
          @pointerup="alSoltar"
          @pointercancel="alSoltar"
        >
          <span class="h-1 w-10 rounded-full bg-arena-300" aria-hidden="true" />
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
          <slot />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
