<script setup>
import { computed, ref } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import EstadoChip from '@/components/EstadoChip.vue'
import MemberChip from '@/components/MemberChip.vue'
import StatusStripe from '@/components/StatusStripe.vue'
import { estadoDe, formateaCantidad, unidadPara } from '@/composables/useEstadoProducto'

/**
 * Fila de producto del listado. Anatomía: franja de estado → nombre →
 * chip de urgencia → cantidad → stepper. El stepper vive aquí dentro para
 * que consumir una unidad sea un solo toque.
 *
 * Gesto: arrastrar a la izquierda consume 1 a nombre del miembro
 * preseleccionado; arrastrar más abre la hoja para elegir cantidad/persona.
 */
const props = defineProps({
  producto: { type: Object, required: true },
  usuarioActual: { type: Object, default: null },
  pendientes: { type: Number, default: 0 },
})

const emit = defineEmits(['consumir', 'anadir', 'abrir', 'abrir-hoja'])

const UMBRAL_CONSUMO = 72
const UMBRAL_HOJA = 168

const estado = computed(() => estadoDe(props.producto))
const unidad = computed(() => props.producto.unidad_medida?.abreviatura ?? 'uds')
const ubicacion = computed(() => props.producto.ubicacion_por_defecto?.nombre ?? null)
const sinStock = computed(() => Number(props.producto.stock_actual ?? 0) <= 0)

// Un producto con varios lotes lo indica en vez de la ubicación, como en
// el mockup ("2 lotes"), porque es lo que cambia la decisión de consumo.
const detalle = computed(() => {
  if ((props.producto.lotes ?? 0) > 1) return `${props.producto.lotes} lotes`
  return ubicacion.value
})

/** Movimiento a partir del cual dejamos de considerarlo un toque. */
const UMBRAL_GESTO = 8

const desplazamiento = ref(0)
const arrastrando = ref(false)
let inicioX = 0
let inicioY = 0
let punteroId = null
let huboGesto = false

function alPulsar(evento) {
  // El stepper tiene sus propias acciones: no debe iniciar el gesto. El
  // resto de la fila sí, incluido el nombre, porque en el móvil el pulgar
  // arrastra justo por ahí.
  if (evento.target.closest('[data-sin-gesto]')) return

  punteroId = evento.pointerId
  inicioX = evento.clientX
  inicioY = evento.clientY
  arrastrando.value = true
  huboGesto = false
}

function alMover(evento) {
  if (!arrastrando.value || evento.pointerId !== punteroId) return

  const deltaX = evento.clientX - inicioX
  const deltaY = evento.clientY - inicioY

  // Si el dedo va claramente en vertical, es scroll de la lista: no se
  // secuestra el gesto.
  if (!huboGesto && Math.abs(deltaY) > Math.abs(deltaX)) return
  if (!huboGesto && Math.abs(deltaX) < UMBRAL_GESTO) return

  if (!huboGesto) {
    // Se captura al empezar el arrastre (no antes): al desplazarse la fila
    // el dedo acaba sobre otro elemento y sin captura no llegaría el
    // pointerup. Hacerlo ya en pointerdown rompería los toques simples,
    // porque el click pasaría a dispararse en el contenedor.
    evento.currentTarget.setPointerCapture?.(evento.pointerId)
  }

  huboGesto = true
  // Solo hacia la izquierda: hacia la derecha no hay acción asociada.
  desplazamiento.value = Math.min(0, Math.max(deltaX, -UMBRAL_HOJA - 40))
}

function alSoltar(evento) {
  if (!arrastrando.value || evento.pointerId !== punteroId) return

  const recorrido = Math.abs(desplazamiento.value)
  arrastrando.value = false
  desplazamiento.value = 0
  evento.currentTarget.releasePointerCapture?.(punteroId)
  punteroId = null

  if (recorrido >= UMBRAL_HOJA) {
    emit('abrir-hoja')
  } else if (recorrido >= UMBRAL_CONSUMO && !sinStock.value) {
    emit('consumir', 1)
  }
}

/** Un arrastre no debe acabar abriendo la ficha al levantar el dedo. */
function alAbrir() {
  if (huboGesto) {
    huboGesto = false
    return
  }
  emit('abrir')
}

const intensidadGesto = computed(() =>
  Math.min(1, Math.abs(desplazamiento.value) / UMBRAL_CONSUMO),
)
const gestoAbreHoja = computed(() => Math.abs(desplazamiento.value) >= UMBRAL_HOJA)
</script>

<template>
  <div class="relative overflow-hidden rounded-md">
    <!-- Lo que el gesto revela debajo de la fila. -->
    <div
      class="absolute inset-0 flex items-center justify-end gap-3 rounded-md bg-acento-500 pr-5 text-fondo"
      :style="{ opacity: intensidadGesto }"
      aria-hidden="true"
    >
      <MemberChip v-if="usuarioActual" :usuario="usuarioActual" tamano="sm" />
      <span class="flex items-center gap-1 font-display text-[18px]">
        <AppIcon name="flechaAbajo" :size="18" />
        {{ gestoAbreHoja ? 'elegir' : '−1' }}
      </span>
    </div>

    <div
      class="relative flex touch-pan-y items-center gap-3 rounded-md py-3 pr-3 pl-4 shadow-sm"
      :class="[
        estado.requiereAccion ? 'bg-acento-100' : 'bg-tarjeta',
        arrastrando ? '' : 'transition-transform duration-200',
      ]"
      :style="{ transform: `translateX(${desplazamiento}px)` }"
      @pointerdown="alPulsar"
      @pointermove="alMover"
      @pointerup="alSoltar"
      @pointercancel="alSoltar"
    >
      <StatusStripe :tono="estado.tono" />

      <button
        type="button"
        class="min-w-0 flex-1 text-left"
        @click="alAbrir"
      >
        <p class="truncate text-[16px] font-bold">{{ producto.nombre }}</p>
        <div class="mt-1.5 flex flex-wrap items-center gap-2">
          <EstadoChip
            v-if="estado.texto"
            :tono="estado.tono"
            :icono="estado.icono"
            :texto="estado.texto"
            tamano="sm"
          />
          <span v-else-if="estado.textoLlano" class="text-[13px] text-arena-500">
            {{ estado.textoLlano }}
          </span>
          <span v-if="detalle" class="text-[13px] text-arena-500">{{ detalle }}</span>
          <EstadoChip
            v-if="pendientes > 0"
            tono="pendiente"
            icono="sincronizar"
            :texto="String(pendientes)"
            tamano="sm"
          />
        </div>
      </button>

      <div class="flex shrink-0 items-center gap-2" data-sin-gesto>
        <button
          type="button"
          class="flex h-11 w-11 items-center justify-center rounded-full border border-arena-300 bg-fondo text-arena-700 transition active:scale-95 disabled:opacity-35"
          :disabled="sinStock"
          :aria-label="`Consumir 1 de ${producto.nombre}`"
          @click="emit('consumir', 1)"
        >
          <AppIcon name="menos" :size="20" />
        </button>

        <div class="flex min-w-[3.5ch] flex-col items-center leading-none">
          <span class="font-display text-[22px] tabular-nums">
            {{ formateaCantidad(producto.stock_actual) }}
          </span>
          <span class="mt-1 text-[11px] font-medium text-arena-500">{{ unidadPara(producto.stock_actual, unidad) }}</span>
        </div>

        <button
          type="button"
          class="flex h-11 w-11 items-center justify-center rounded-full bg-acento-500 text-fondo shadow-sm transition active:scale-95"
          :aria-label="`Añadir stock de ${producto.nombre}`"
          @click="emit('anadir')"
        >
          <AppIcon name="mas" :size="20" />
        </button>
      </div>
    </div>
  </div>
</template>
