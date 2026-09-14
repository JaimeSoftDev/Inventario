<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import QtyStepper from '@/components/QtyStepper.vue'
import SelectorMiembro from '@/components/SelectorMiembro.vue'
import { formateaCantidad, unidadPara } from '@/composables/useEstadoProducto'
import { useMovimientoStock } from '@/composables/useMovimientoStock'
import { mostrarToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { useCatalogosStore } from '@/stores/catalogos'

/**
 * Alta de stock (compra). Cada lote entra con su propia caducidad, que es
 * lo que después permite aplicar FEFO al consumir.
 */
const props = defineProps({
  producto: { type: Object, default: null },
})

const emit = defineEmits(['cerrar', 'registrado'])

const auth = useAuthStore()
const catalogos = useCatalogosStore()
const { registrarCompra } = useMovimientoStock()

const cantidad = ref(1)
const ubicacionId = ref(null)
const fechaCaducidad = ref('')
const compradoPor = ref(null)
const enviando = ref(false)
const error = ref(null)

onMounted(() => {
  if (catalogos.ubicaciones.length === 0) catalogos.cargarUbicaciones()
})

watch(
  () => props.producto,
  (producto) => {
    if (!producto) return

    cantidad.value = 1
    error.value = null
    compradoPor.value = auth.usuario?.id ?? null
    ubicacionId.value = producto.ubicacion_por_defecto?.id ?? catalogos.ubicaciones[0]?.id ?? null

    // Si el producto declara una caducidad típica, se propone ya resuelta.
    fechaCaducidad.value = producto.dias_caducidad_por_defecto
      ? enDias(producto.dias_caducidad_por_defecto)
      : ''
  },
)

const abierta = computed(() => props.producto !== null)
const unidad = computed(() => props.producto?.unidad_medida?.abreviatura ?? 'uds')

function enDias(dias) {
  const fecha = new Date()
  fecha.setDate(fecha.getDate() + dias)
  return fecha.toISOString().slice(0, 10)
}

const ATAJOS_CADUCIDAD = [
  { etiqueta: '+7 días', dias: 7 },
  { etiqueta: '+1 mes', dias: 30 },
  { etiqueta: '+3 meses', dias: 90 },
]

const diasRestantes = computed(() => {
  if (!fechaCaducidad.value) return null
  const destino = new Date(fechaCaducidad.value)
  const hoy = new Date()
  destino.setHours(0, 0, 0, 0)
  hoy.setHours(0, 0, 0, 0)
  return Math.round((destino - hoy) / 86400000)
})

const fechaLegible = computed(() => {
  if (!fechaCaducidad.value) return 'Sin caducidad'
  return new Date(fechaCaducidad.value).toLocaleDateString('es-ES', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
})

async function confirmar() {
  if (!props.producto || enviando.value) return

  enviando.value = true
  error.value = null

  try {
    await registrarCompra({
      producto_id: props.producto.id,
      ubicacion_id: ubicacionId.value,
      cantidad: cantidad.value,
      fecha_caducidad: fechaCaducidad.value || null,
      usuario_atribuido_id: compradoPor.value,
    })

    mostrarToast({
      texto: `+${formateaCantidad(cantidad.value)} ${props.producto.nombre}`,
    })

    emit('registrado')
    emit('cerrar')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'No se pudo registrar la compra.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <BottomSheet :abierta="abierta" alta @cerrar="emit('cerrar')">
    <div v-if="producto" class="flex h-full flex-col">
      <div class="flex-1 overflow-y-auto px-5 pt-2 pb-4">
        <header class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h2 class="text-[28px] leading-tight">Añadir stock</h2>
            <p class="mt-1 truncate text-[14px] text-arena-500">
              {{ producto.nombre }}
              <template v-if="producto.descripcion"> · {{ producto.descripcion }}</template>
            </p>
          </div>
          <button
            type="button"
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-arena-300 text-arena-700"
            aria-label="Cerrar"
            @click="emit('cerrar')"
          >
            <AppIcon name="cerrar" :size="20" />
          </button>
        </header>

        <section class="mt-6">
          <h3 class="etiqueta-seccion">Cantidad</h3>
          <div class="mt-2.5 rounded-lg bg-tarjeta px-6 py-4">
            <QtyStepper v-model="cantidad" :unidad="unidad" :min="1" tamano="lg" />
          </div>
        </section>

        <section class="mt-6">
          <h3 class="etiqueta-seccion">Caducidad</h3>

          <label
            class="mt-2.5 flex items-center gap-3 rounded-md border border-arena-300 bg-arena-50 px-4 py-3.5"
          >
            <AppIcon name="calendario" :size="20" class="shrink-0 text-acento-600" />
            <span class="flex-1 text-[16px] font-bold">{{ fechaLegible }}</span>
            <span v-if="diasRestantes !== null" class="text-[13px] text-arena-500">
              en {{ diasRestantes }} días
            </span>
            <input v-model="fechaCaducidad" type="date" class="sr-only" />
          </label>

          <div class="mt-3 flex flex-wrap gap-2">
            <button
              v-for="atajo in ATAJOS_CADUCIDAD"
              :key="atajo.dias"
              type="button"
              class="h-11 rounded-full px-4 text-[14px] font-bold transition"
              :class="
                diasRestantes === atajo.dias
                  ? 'bg-acento-500 text-fondo'
                  : 'border border-arena-300 text-arena-700'
              "
              @click="fechaCaducidad = enDias(atajo.dias)"
            >
              {{ atajo.etiqueta }}
            </button>
            <button
              type="button"
              class="h-11 rounded-full px-4 text-[14px] font-bold transition"
              :class="
                fechaCaducidad === ''
                  ? 'bg-tinta text-fondo'
                  : 'border border-arena-300 text-arena-700'
              "
              @click="fechaCaducidad = ''"
            >
              Sin caducidad
            </button>
          </div>
        </section>

        <section class="mt-6">
          <h3 class="etiqueta-seccion">Ubicación</h3>
          <div class="mt-2.5 flex flex-wrap gap-2">
            <button
              v-for="ubicacion in catalogos.ubicaciones"
              :key="ubicacion.id"
              type="button"
              class="h-11 rounded-full px-4 text-[14px] font-bold transition"
              :class="
                ubicacionId === ubicacion.id
                  ? 'bg-oliva-500 text-fondo'
                  : 'border border-arena-300 text-arena-700'
              "
              @click="ubicacionId = ubicacion.id"
            >
              {{ ubicacion.nombre }}
            </button>
          </div>
        </section>

        <section class="mt-6">
          <SelectorMiembro v-model="compradoPor" etiqueta="Comprado por" />
        </section>

        <p v-if="error" class="mt-4 rounded-md bg-acento-100 px-4 py-3 text-[14px] text-acento-800">
          {{ error }}
        </p>
      </div>

      <div class="shrink-0 border-t border-arena-200 px-5 pt-3 pb-5">
        <AppButton
          tamano="lg"
          icono="carrito"
          bloque
          :deshabilitado="enviando || !ubicacionId || !compradoPor"
          @click="confirmar"
        >
          Añadir {{ formateaCantidad(cantidad) }} {{ unidadPara(cantidad, unidad) }}
        </AppButton>
      </div>
    </div>
  </BottomSheet>
</template>
