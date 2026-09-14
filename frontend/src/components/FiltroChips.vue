<script setup>
/**
 * Fila de filtros deslizable horizontalmente (ubicaciones en el stock,
 * tipo de movimiento en el histórico). El activo va en tinta sólida para
 * que se distinga sin depender del color de acento.
 */
defineProps({
  modelValue: { type: [String, Number, null], default: null },
  opciones: { type: Array, required: true }, // [{ valor, etiqueta }]
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="-mx-5 overflow-x-auto px-5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
    <div class="flex w-max gap-2">
      <button
        v-for="opcion in opciones"
        :key="String(opcion.valor)"
        type="button"
        class="h-11 shrink-0 rounded-full px-4 text-[14px] font-bold transition"
        :class="
          modelValue === opcion.valor
            ? 'bg-tinta text-fondo'
            : 'border border-arena-300 bg-fondo text-arena-700'
        "
        @click="emit('update:modelValue', opcion.valor)"
      >
        {{ opcion.etiqueta }}
      </button>
    </div>
  </div>
</template>
