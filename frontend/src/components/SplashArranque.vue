<script setup>
import AppIcon from '@/components/AppIcon.vue'

/**
 * Arranque en frío de la PWA: mientras se abre la base local se muestra la
 * marca y qué está pasando, en vez de un spinner ambiguo que no dice si la
 * app está viva o rota. Si vamos sin conexión, se dice ya aquí.
 */
defineProps({
  enLinea: { type: Boolean, default: true },
  fechaDatos: { type: String, default: null },
})
</script>

<template>
  <div class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-fondo px-8">
    <span
      class="flex h-24 w-24 items-center justify-center rounded-[30px] bg-acento-500 text-fondo shadow-md"
    >
      <AppIcon name="caja" :size="48" />
    </span>

    <h1 class="mt-7 text-[38px] leading-none">Despensa</h1>
    <p class="mt-3 text-[15px] text-arena-600">Cargando inventario local…</p>

    <div class="mt-6 h-1.5 w-52 overflow-hidden rounded-full bg-arena-200">
      <span class="block h-full w-1/3 animate-pulse rounded-full bg-oliva-500" />
    </div>

    <p
      v-if="!enLinea"
      class="absolute bottom-12 flex items-center gap-2 rounded-full border-[1.5px] border-dashed border-arena-300 px-4 py-2.5 text-[13px] text-arena-600"
    >
      <span class="h-2 w-2 rounded-full bg-oliva-500" aria-hidden="true" />
      Modo offline
      <template v-if="fechaDatos"> · datos del {{ fechaDatos }}</template>
    </p>
  </div>
</template>
