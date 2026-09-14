<script setup>
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { formateaCantidad } from '@/composables/useEstadoProducto'

/**
 * Stepper de cantidad. Vive dentro de la propia tarjeta: consumir una
 * unidad es un solo toque, sin abrir nada. El "+" es la acción positiva
 * (acento sólido) y el "−" la sustractiva (superficie), para que no se
 * confundan con el pulgar.
 */
const props = defineProps({
  modelValue: { type: Number, required: true },
  unidad: { type: String, default: null },
  min: { type: Number, default: 0 },
  max: { type: Number, default: Number.POSITIVE_INFINITY },
  paso: { type: Number, default: 1 },
  tamano: { type: String, default: 'md' }, // md | lg
  deshabilitado: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'restar', 'sumar'])

const grande = computed(() => props.tamano === 'lg')
const botonClase = computed(() =>
  grande.value ? 'h-14 w-14' : 'h-11 w-11',
)
const cifraClase = computed(() =>
  grande.value ? 'text-[40px]' : 'text-[22px]',
)

const puedeRestar = computed(() => !props.deshabilitado && props.modelValue - props.paso >= props.min)
const puedeSumar = computed(() => !props.deshabilitado && props.modelValue + props.paso <= props.max)

function restar() {
  if (!puedeRestar.value) return
  emit('update:modelValue', Number((props.modelValue - props.paso).toFixed(3)))
  emit('restar')
}

function sumar() {
  if (!puedeSumar.value) return
  emit('update:modelValue', Number((props.modelValue + props.paso).toFixed(3)))
  emit('sumar')
}
</script>

<template>
  <div class="flex items-center" :class="grande ? 'w-full justify-between gap-4' : 'gap-2'">
    <button
      type="button"
      class="flex shrink-0 items-center justify-center rounded-full border border-arena-300 bg-fondo text-arena-700 transition active:scale-95 disabled:opacity-35"
      :class="botonClase"
      :disabled="!puedeRestar"
      :aria-label="`Quitar ${paso}`"
      @click="restar"
    >
      <AppIcon name="menos" :size="grande ? 24 : 20" />
    </button>

    <div class="flex min-w-[3ch] flex-col items-center leading-none">
      <span class="font-display tabular-nums" :class="cifraClase">
        {{ formateaCantidad(modelValue) }}
      </span>
      <span v-if="unidad" class="mt-1 text-[11px] font-medium text-arena-500">{{ unidad }}</span>
    </div>

    <button
      type="button"
      class="flex shrink-0 items-center justify-center rounded-full bg-acento-500 text-fondo shadow-sm transition active:scale-95 disabled:opacity-35"
      :class="botonClase"
      :disabled="!puedeSumar"
      :aria-label="`Añadir ${paso}`"
      @click="sumar"
    >
      <AppIcon name="mas" :size="grande ? 24 : 20" />
    </button>
  </div>
</template>
