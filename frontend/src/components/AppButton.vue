<script setup>
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

/**
 * Botón del sistema. Todas las variantes son píldoras (radio 999) con
 * altura mínima de 44px para cumplir el objetivo táctil.
 */
const props = defineProps({
  variante: { type: String, default: 'primario' }, // primario | secundario | claro | contorno-claro | fantasma
  tamano: { type: String, default: 'md' }, // sm | md | lg
  icono: { type: String, default: null },
  iconoDerecha: { type: String, default: null },
  fuente: { type: String, default: 'display' }, // display | sans
  bloque: { type: Boolean, default: false },
  deshabilitado: { type: Boolean, default: false },
})

const VARIANTES = {
  primario: 'bg-acento-500 text-fondo shadow-md active:bg-acento-600',
  secundario: 'border border-arena-300 bg-fondo text-tinta active:bg-arena-200',
  // Sobre superficie oscura (tarjetas de conflicto).
  claro: 'bg-acento-50 text-acento-800 active:bg-acento-100',
  'contorno-claro': 'border border-acento-200/50 text-acento-50 active:bg-acento-800',
  fantasma: 'text-acento-600 active:bg-acento-100',
}

const TAMANOS = {
  sm: 'h-10 px-4 text-[14px] gap-1.5',
  md: 'h-12 px-5 text-[16px] gap-2',
  lg: 'h-15 px-6 text-[20px] gap-2.5',
}

const clases = computed(() => [
  VARIANTES[props.variante] ?? VARIANTES.primario,
  TAMANOS[props.tamano] ?? TAMANOS.md,
  props.fuente === 'display' ? 'font-display' : 'font-sans font-bold',
  props.bloque ? 'w-full' : '',
])
</script>

<template>
  <button
    type="button"
    class="inline-flex items-center justify-center rounded-full whitespace-nowrap transition active:scale-[0.98] disabled:opacity-40 disabled:active:scale-100"
    :class="clases"
    :disabled="deshabilitado"
  >
    <AppIcon v-if="icono" :name="icono" :size="tamano === 'lg' ? 22 : 18" />
    <slot />
    <AppIcon v-if="iconoDerecha" :name="iconoDerecha" :size="tamano === 'lg' ? 22 : 18" />
  </button>
</template>
