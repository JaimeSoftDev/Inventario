<script setup>
import AppIcon from '@/components/AppIcon.vue'

/**
 * Navegación principal: tres destinos, siempre visibles, en la zona baja
 * de la pantalla. Activo = acento + peso; nunca solo color.
 */
const DESTINOS = [
  { nombre: 'stock', etiqueta: 'Stock', icono: 'caja' },
  { nombre: 'historico', etiqueta: 'Histórico', icono: 'lista' },
  { nombre: 'ajustes', etiqueta: 'Ajustes', icono: 'ajustes' },
]
</script>

<template>
  <nav
    class="sticky bottom-0 z-20 mt-auto border-t border-arena-200 bg-fondo/95 backdrop-blur-sm"
    style="padding-bottom: env(safe-area-inset-bottom)"
  >
    <ul class="mx-auto flex max-w-[460px]">
      <li v-for="destino in DESTINOS" :key="destino.nombre" class="flex-1">
        <RouterLink
          :to="{ name: destino.nombre }"
          class="flex h-16 flex-col items-center justify-center gap-1 text-arena-500"
          active-class="text-acento-600"
        >
          <AppIcon :name="destino.icono" :size="22" />
          <span class="text-[11px] font-bold">{{ destino.etiqueta }}</span>
        </RouterLink>
      </li>
    </ul>
  </nav>
</template>
