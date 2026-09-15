<script setup>
import { computed, ref } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { formateaCantidad } from '@/composables/useEstadoProducto'

/**
 * Stepper de cantidad. Vive dentro de la propia tarjeta: consumir una
 * unidad es un solo toque, sin abrir nada. El "+" es la acción positiva
 * (acento sólido) y el "−" la sustractiva (superficie), para que no se
 * confundan con el pulgar.
 *
 * La cifra central también se escribe. Los botones sirven para ajustar de
 * uno en uno, pero "12 latas" a toques es absurdo, así que el número es un
 * campo de verdad: al enfocarlo se selecciona entero, de modo que teclear
 * sustituye en lugar de añadir dígitos al final.
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

/** Las cantidades se guardan con tres decimales; más es ruido. */
function redondea(valor) {
  return Number(valor.toFixed(3))
}

function acota(valor) {
  return Math.max(Math.min(valor, props.max), props.min)
}

function restar() {
  if (!puedeRestar.value) return
  emit('update:modelValue', redondea(props.modelValue - props.paso))
  emit('restar')
}

function sumar() {
  if (!puedeSumar.value) return
  emit('update:modelValue', redondea(props.modelValue + props.paso))
  emit('sumar')
}

// --- Cifra editable ------------------------------------------------------
//
// Mientras se escribe manda el borrador, no el modelo: si el campo se
// repintara desde `modelValue` en cada tecla, acotar o redondear borraría
// lo que la persona está tecleando a medias ("1." o un "20" cuyo primer
// dígito aún no es el número final).

const editando = ref(false)
const borrador = ref('')

const textoVisible = computed(() =>
  editando.value ? borrador.value : formateaCantidad(props.modelValue),
)

/** Deja solo cifras y un único separador decimal, en formato máquina. */
function sanea(texto) {
  const [entera, ...decimales] = texto.replace(/[^\d.,]/g, '').replace(/,/g, '.').split('.')
  return decimales.length ? `${entera}.${decimales.join('')}` : entera
}

function parsea(texto) {
  if (texto === '' || texto === '.') return null
  const numero = Number(texto)
  return Number.isFinite(numero) ? numero : null
}

const hayMaximo = computed(() => Number.isFinite(props.max))

/**
 * Se avisa cuando lo tecleado pasa del máximo, porque el valor que se
 * enviará no es el que se está viendo. Con texto, no solo con color.
 */
const excedeMaximo = computed(() => {
  if (!editando.value || !hayMaximo.value) return false
  const valor = parsea(borrador.value)
  return valor !== null && valor > props.max
})

function alEnfocar(evento) {
  // El borrador arranca con el mismo texto que ya se ve, así que el campo
  // no cambia de valor y se puede seleccionar sin esperar a un repintado.
  borrador.value = formateaCantidad(props.modelValue)
  editando.value = true
  evento.target.select()
}

function alEscribir(evento) {
  const saneado = sanea(evento.target.value)
  borrador.value = saneado

  // Solo se reescribe el campo si el saneado ha quitado algo: asignar
  // `value` manda el cursor al final, y hacerlo en cada tecla impediría
  // corregir un dígito del medio.
  if (evento.target.value !== saneado) evento.target.value = saneado

  const valor = parsea(saneado)
  if (valor !== null) emit('update:modelValue', redondea(acota(valor)))
}

function alSalir() {
  editando.value = false
  // Un campo vacío no es una cantidad: se vuelve al último valor válido.
  const valor = parsea(borrador.value)
  if (valor !== null) emit('update:modelValue', redondea(acota(valor)))
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

    <div
      class="flex flex-col items-center leading-none"
      :class="grande ? 'min-w-0 flex-1' : 'w-[4.5ch]'"
    >
      <input
        :value="textoVisible"
        type="text"
        inputmode="decimal"
        enterkeyhint="done"
        :disabled="deshabilitado"
        :aria-label="unidad ? `Cantidad en ${unidad}` : 'Cantidad'"
        class="w-full border-0 border-b bg-transparent p-0 text-center font-display tabular-nums outline-none transition-colors disabled:opacity-35"
        :class="[
          cifraClase,
          editando ? 'border-acento-500' : 'border-arena-300',
        ]"
        @focus="alEnfocar"
        @input="alEscribir"
        @blur="alSalir"
        @keydown.enter.prevent="$event.target.blur()"
      />
      <span
        v-if="excedeMaximo"
        class="mt-1 text-[11px] font-bold text-acento-600"
      >
        máx. {{ formateaCantidad(max) }}
      </span>
      <span v-else-if="unidad" class="mt-1 text-[11px] font-medium text-arena-500">{{ unidad }}</span>
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
