<script setup>
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

/**
 * Patrón offline, repetido en toda la app:
 *  - Discontinuo = escrito en local, aún no confirmado (informativo).
 *  - Terracota sólido = conflicto que el usuario debe resolver (accionable).
 *
 * No aparece nada si hay conexión y la cola está vacía.
 */
const props = defineProps({
  enLinea: { type: Boolean, default: true },
  pendientes: { type: Number, default: 0 },
  conflictos: { type: Number, default: 0 },
})

const emit = defineEmits(['ver'])

const hayConflicto = computed(() => props.conflictos > 0)

const texto = computed(() => {
  if (hayConflicto.value) {
    return props.conflictos === 1
      ? '1 movimiento necesita revisión'
      : `${props.conflictos} movimientos necesitan revisión`
  }

  // Sin nada en cola, el contador solo añade ruido.
  if (props.pendientes === 0) return 'Sin conexión'

  const cola = props.pendientes === 1 ? '1 pendiente' : `${props.pendientes} pendientes`
  if (!props.enLinea) return `Sin conexión · ${cola}`
  return `Sincronizando · ${cola}`
})

const visible = computed(() => hayConflicto.value || !props.enLinea || props.pendientes > 0)
</script>

<template>
  <button
    v-if="visible"
    type="button"
    class="flex w-full items-center gap-2.5 rounded-full px-4 py-2.5 text-left"
    :class="
      hayConflicto
        ? 'bg-acento-700 text-fondo shadow-sm'
        : 'border-[1.5px] border-dashed border-arena-400 text-arena-600'
    "
    @click="emit('ver')"
  >
    <AppIcon :name="hayConflicto ? 'aviso' : enLinea ? 'sincronizar' : 'sinConexion'" :size="18" />
    <span class="flex-1 text-[14px] font-bold">{{ texto }}</span>
    <span class="text-[14px] font-bold" :class="hayConflicto ? 'text-acento-50' : 'text-acento-600'">
      {{ hayConflicto ? 'Revisar' : 'Ver' }}
    </span>
  </button>
</template>
