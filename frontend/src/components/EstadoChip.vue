<script setup>
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

/**
 * Chip de estado: color + icono + texto, los tres siempre juntos.
 * Cubre caducidad ("2 d"), stock bajo ("mín. 4"), pendiente de sincronizar
 * y conflicto a revisar.
 */
const props = defineProps({
  tono: { type: String, default: 'oliva' }, // oliva | bajo | acento | pendiente | revisar | neutro
  icono: { type: String, default: null },
  texto: { type: String, required: true },
  tamano: { type: String, default: 'md' }, // sm | md
})

const TONOS = {
  oliva: 'bg-oliva-100 text-oliva-700',
  bajo: 'bg-acento-100 text-acento-700',
  acento: 'bg-acento-100 text-acento-700',
  // Pendiente = escrito en local, aún no confirmado: borde discontinuo.
  pendiente: 'border-[1.5px] border-dashed border-arena-400 text-arena-600',
  // Revisar = conflicto que el usuario debe resolver: terracota sólido.
  revisar: 'bg-acento-700 text-fondo',
  neutro: 'bg-arena-200 text-arena-700',
}

const clases = computed(() => TONOS[props.tono] ?? TONOS.neutro)
const alto = computed(() => (props.tamano === 'sm' ? 'h-6 px-2 text-[11px]' : 'h-7 px-2.5 text-[12px]'))
</script>

<template>
  <span
    class="inline-flex shrink-0 items-center gap-1 rounded-full font-semibold whitespace-nowrap"
    :class="[clases, alto]"
  >
    <AppIcon v-if="icono" :name="icono" :size="tamano === 'sm' ? 12 : 14" />
    {{ texto }}
  </span>
</template>
