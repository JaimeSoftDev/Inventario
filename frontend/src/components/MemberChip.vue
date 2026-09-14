<script setup>
import { computed } from 'vue'
import { colorDe, inicialesDe } from '@/composables/useMiembros'

/**
 * Avatar circular de un miembro del hogar.
 *
 * Variantes:
 *  - `solido` (por defecto): a quién se atribuye el movimiento.
 *  - `discontinuo`: quién lo registró, cuando no coincide con el atribuido.
 *    El borde discontinuo es el mismo lenguaje que usa la cola offline para
 *    decir "esto es contexto, no el dato principal".
 */
const props = defineProps({
  usuario: { type: Object, default: null },
  tamano: { type: String, default: 'md' }, // sm | md | lg
  variante: { type: String, default: 'solido' }, // solido | discontinuo
  seleccionado: { type: Boolean, default: false },
  etiqueta: { type: String, default: null }, // texto extra ("reg. Ana")
})

const TAMANOS = {
  sm: { caja: 'h-7 w-7', texto: 'text-[11px]' },
  md: { caja: 'h-9 w-9', texto: 'text-[13px]' },
  lg: { caja: 'h-14 w-14', texto: 'text-[19px]' },
}

const medidas = computed(() => TAMANOS[props.tamano] ?? TAMANOS.md)
const iniciales = computed(() => inicialesDe(props.usuario?.name))
const paleta = computed(() => colorDe(props.usuario?.id))
const esDiscontinuo = computed(() => props.variante === 'discontinuo')

const estilo = computed(() =>
  esDiscontinuo.value
    ? { color: 'var(--color-arena-600)' }
    : { backgroundColor: paleta.value.fondo, color: paleta.value.texto },
)
</script>

<template>
  <span
    class="inline-flex shrink-0 items-center justify-center rounded-full font-display leading-none"
    :class="[
      medidas.caja,
      medidas.texto,
      esDiscontinuo ? 'border-[1.5px] border-dashed border-arena-400' : '',
      seleccionado ? 'ring-2 ring-acento-500 ring-offset-2 ring-offset-fondo' : '',
    ]"
    :style="estilo"
    :title="usuario?.name"
  >
    {{ iniciales }}
  </span>
</template>
