<script setup>
import { computed } from 'vue'

/**
 * Franja vertical de estado en el borde izquierdo de una tarjeta. Es el
 * primer elemento de la anatomía de la tarjeta de producto: da el estado de
 * un vistazo antes incluso de leer el nombre.
 */
const props = defineProps({
  tono: { type: String, default: 'oliva' }, // oliva | bajo | acento | neutro
})

const COLORES = {
  oliva: 'var(--color-oliva-500)',
  bajo: 'var(--color-acento-400)',
  acento: 'var(--color-acento-500)',
  neutro: 'var(--color-arena-300)',
}

const color = computed(() => COLORES[props.tono] ?? COLORES.neutro)
</script>

<template>
  <span
    class="absolute inset-y-2 left-0 w-[4px] rounded-full"
    :style="{ backgroundColor: color }"
    aria-hidden="true"
  />
</template>
