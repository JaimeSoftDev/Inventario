<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
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
 * Gestos, ambos consumen una unidad y se diferencian solo en a quién se
 * atribuye:
 *  - izquierda: a nombre del usuario actual, sin más pasos. Arrastrando
 *    más se abre la hoja para elegir cantidad y persona.
 *  - derecha: a nombre de otro miembro. Al soltar, la fila se convierte en
 *    una tira de miembros y basta un toque. Es deslizar + tocar, en lugar
 *    de abrir la hoja y confirmar.
 *
 * El gesto derecho no existe para quien no puede atribuir a otros: sin el
 * permiso la petición acabaría en 403, así que no se ofrece.
 */
const props = defineProps({
  producto: { type: Object, required: true },
  usuarioActual: { type: Object, default: null },
  pendientes: { type: Number, default: 0 },
  miembros: { type: Array, default: () => [] },
  puedeAtribuir: { type: Boolean, default: false },
})

const emit = defineEmits(['consumir', 'consumir-por', 'anadir', 'abrir', 'abrir-hoja'])

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

/**
 * Solo el resto del hogar: para uno mismo ya está el gesto izquierdo, y
 * repetirse aquí robaría sitio a los que de verdad hacen falta.
 */
const otrosMiembros = computed(() =>
  props.miembros.filter((miembro) => miembro.id !== props.usuarioActual?.id),
)

const hayGestoDerecha = computed(
  () => props.puedeAtribuir && otrosMiembros.value.length > 0 && !sinStock.value,
)

/** Movimiento a partir del cual dejamos de considerarlo un toque. */
const UMBRAL_GESTO = 8

const desplazamiento = ref(0)
const arrastrando = ref(false)
const eligiendo = ref(false)
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

  if (deltaX < 0) {
    desplazamiento.value = Math.max(deltaX, -UMBRAL_HOJA - 40)
  } else {
    // Hacia la derecha la fila no se despega si no hay a quién atribuir:
    // el gesto se traga sin hacer nada en vez de prometer una acción que
    // luego no existe.
    desplazamiento.value = hayGestoDerecha.value
      ? Math.min(deltaX, UMBRAL_CONSUMO + 40)
      : 0
  }
}

function alSoltar(evento) {
  if (!arrastrando.value || evento.pointerId !== punteroId) return

  const recorrido = desplazamiento.value
  arrastrando.value = false
  desplazamiento.value = 0
  evento.currentTarget.releasePointerCapture?.(punteroId)
  punteroId = null

  if (recorrido > 0) {
    if (recorrido >= UMBRAL_CONSUMO && hayGestoDerecha.value) eligiendo.value = true
    return
  }

  const izquierda = Math.abs(recorrido)
  if (izquierda >= UMBRAL_HOJA) {
    emit('abrir-hoja')
  } else if (izquierda >= UMBRAL_CONSUMO && !sinStock.value) {
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

function elegir(miembro) {
  eligiendo.value = false
  emit('consumir-por', miembro)
}

// Tocar en cualquier otro sitio cancela: es lo que se espera de algo que
// aparece encima de la lista, y evita dejar varias filas abiertas.
const raiz = ref(null)

function alTocarFuera(evento) {
  if (!raiz.value?.contains(evento.target)) eligiendo.value = false
}

watch(eligiendo, (activo) => {
  if (activo) document.addEventListener('pointerdown', alTocarFuera, true)
  else document.removeEventListener('pointerdown', alTocarFuera, true)
})

onBeforeUnmount(() => document.removeEventListener('pointerdown', alTocarFuera, true))

const intensidadGesto = computed(() =>
  Math.min(1, Math.abs(desplazamiento.value) / UMBRAL_CONSUMO),
)
const gestoAbreHoja = computed(() => desplazamiento.value <= -UMBRAL_HOJA)
const gestoAtribuye = computed(() => desplazamiento.value > 0)
</script>

<template>
  <div ref="raiz" class="relative overflow-hidden rounded-md">
    <!-- Lo que el gesto revela debajo de la fila. Izquierda: consumo a tu
         nombre. Derecha: consumo a nombre de otro. -->
    <div
      v-if="gestoAtribuye"
      class="absolute inset-0 flex items-center gap-2 rounded-md bg-tinta pl-5 text-fondo"
      :style="{ opacity: intensidadGesto }"
      aria-hidden="true"
    >
      <!-- Solo se ve lo que el dedo ha destapado, así que el texto tiene
           que caber en esos pocos píxeles: el icono de personas es quien
           distingue este gesto del de la izquierda, y la tira que aparece
           al soltar ya lo dice con todas las letras. -->
      <AppIcon name="personas" :size="18" />
      <span class="font-display text-[18px] whitespace-nowrap">−1</span>
    </div>
    <div
      v-else
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

    <!-- Tira de miembros: el paso que sustituye a abrir la hoja entera. -->
    <div
      v-if="eligiendo"
      class="relative rounded-md bg-tinta px-4 py-2.5 text-fondo shadow-sm"
    >
      <div class="flex items-center justify-between gap-3">
        <p class="text-[11px] font-bold tracking-[0.08em] text-fondo/70 uppercase">
          −1 {{ producto.nombre }} · a nombre de
        </p>
        <button
          type="button"
          class="-mr-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-fondo/70"
          aria-label="Cancelar"
          @click="eligiendo = false"
        >
          <AppIcon name="cerrar" :size="16" />
        </button>
      </div>

      <!-- Píldoras en crema, no sobre el oscuro: algunos avatares del
           hogar son de tono oscuro y sobre la barra desaparecían. -->
      <div class="mt-1.5 flex gap-2 overflow-x-auto pb-0.5">
        <button
          v-for="miembro in otrosMiembros"
          :key="miembro.id"
          type="button"
          class="flex h-10 shrink-0 items-center gap-2 rounded-full bg-fondo pr-3.5 pl-1 text-tinta transition active:scale-95"
          :aria-label="`Consumir 1 de ${producto.nombre} a nombre de ${miembro.name}`"
          @click="elegir(miembro)"
        >
          <MemberChip :usuario="miembro" tamano="md" />
          <span class="text-[14px] font-bold whitespace-nowrap">
            {{ miembro.name.split(' ')[0] }}
          </span>
        </button>
      </div>
    </div>

    <div
      v-else
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
