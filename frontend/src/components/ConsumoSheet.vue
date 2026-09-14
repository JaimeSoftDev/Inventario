<script setup>
import { computed, ref, watch } from 'vue'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import QtyStepper from '@/components/QtyStepper.vue'
import SelectorMiembro from '@/components/SelectorMiembro.vue'
import StatusStripe from '@/components/StatusStripe.vue'
import { useConexion } from '@/composables/useConexion'
import { diasHasta, estadoDe, formateaCantidad, formateaDias } from '@/composables/useEstadoProducto'
import { useMovimientoStock } from '@/composables/useMovimientoStock'
import { mostrarToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { useUsuariosStore } from '@/stores/usuarios'

/**
 * Hoja de consumo: no pierde el contexto del listado y deja cantidad,
 * persona y confirmación al alcance del pulgar.
 */
const props = defineProps({
  producto: { type: Object, default: null },
  // Lote concreto, cuando se consume desde la ficha de producto.
  lote: { type: Object, default: null },
})

const emit = defineEmits(['cerrar', 'registrado'])

const auth = useAuthStore()
const usuariosStore = useUsuariosStore()
const { enLinea } = useConexion()
const { registrarConsumo, deshacerConsumo } = useMovimientoStock()

const cantidad = ref(1)
const atribuidoA = ref(null)
const enviando = ref(false)
const error = ref(null)

watch(
  () => props.producto,
  (producto) => {
    if (!producto) return
    cantidad.value = 1
    error.value = null
    atribuidoA.value = auth.usuario?.id ?? null
  },
)

const abierta = computed(() => props.producto !== null)
const unidad = computed(() => props.producto?.unidad_medida?.abreviatura ?? 'uds')
const disponible = computed(() =>
  Number(props.lote ? props.lote.cantidad_restante : (props.producto?.stock_actual ?? 0)),
)
const estado = computed(() => (props.producto ? estadoDe(props.producto) : null))

const contexto = computed(() => {
  if (!props.producto) return ''

  const partes = []
  if (props.producto.ubicacion_por_defecto?.nombre) {
    partes.push(props.producto.ubicacion_por_defecto.nombre)
  }
  partes.push(`quedan ${formateaCantidad(disponible.value)} ${unidad.value}`)

  const dias = diasHasta(props.producto.proxima_caducidad)
  if (dias !== null) partes.push(`caduca en ${formateaDias(dias)}`)

  return partes.join(' · ')
})

/** Atajos: lo que de verdad se suele consumir de un tirón. */
const atajos = computed(() => {
  const total = disponible.value
  const opciones = [{ etiqueta: `todo (${formateaCantidad(total)})`, valor: total }]

  for (const valor of [2, 4]) {
    if (valor < total) opciones.push({ etiqueta: String(valor), valor })
  }

  const dias = diasHasta(props.producto?.proxima_caducidad)
  if (dias !== null && props.producto?.lotes > 1) {
    opciones.push({ etiqueta: `lote ${formateaDias(dias)}`, valor: Math.min(total, 1) })
  }

  return opciones.filter((opcion) => opcion.valor > 0)
})

const nombreAtribuido = computed(
  () => usuariosStore.usuarios.find((usuario) => usuario.id === atribuidoA.value)?.name ?? '',
)

async function confirmar() {
  if (!props.producto || enviando.value) return

  enviando.value = true
  error.value = null

  try {
    const resultado = await registrarConsumo({
      producto_id: props.producto.id,
      cantidad: cantidad.value,
      usuario_atribuido_id: atribuidoA.value,
    })

    mostrarToast({
      texto: `−${formateaCantidad(cantidad.value)} ${props.producto.nombre} · ${nombreAtribuido.value}`,
      usuario: usuariosStore.usuarios.find((usuario) => usuario.id === atribuidoA.value),
      alDeshacer: () => deshacerConsumo(resultado).then(() => emit('registrado')),
    })

    emit('registrado')
    emit('cerrar')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'No se pudo registrar el consumo.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <BottomSheet :abierta="abierta" @cerrar="emit('cerrar')">
    <div v-if="producto" class="px-5 pt-2 pb-6">
      <div class="relative pl-4">
        <StatusStripe :tono="estado.tono" />
        <h2 class="text-[26px] leading-tight">{{ producto.nombre }}</h2>
        <p class="mt-1 text-[14px] text-arena-500">{{ contexto }}</p>
      </div>

      <div class="mt-6">
        <SelectorMiembro v-model="atribuidoA" />
      </div>

      <section class="mt-6">
        <h3 class="etiqueta-seccion">Cantidad</h3>
        <div class="mt-2.5 rounded-lg bg-tarjeta px-6 py-4">
          <QtyStepper
            v-model="cantidad"
            :unidad="unidad"
            :min="1"
            :max="disponible"
            tamano="lg"
          />
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
          <button
            v-for="atajo in atajos"
            :key="atajo.etiqueta"
            type="button"
            class="h-11 rounded-full border px-4 text-[14px] font-bold transition"
            :class="
              cantidad === atajo.valor
                ? 'border-acento-500 bg-acento-100 text-acento-700'
                : 'border-arena-300 text-arena-700'
            "
            @click="cantidad = atajo.valor"
          >
            {{ atajo.etiqueta }}
          </button>
        </div>
      </section>

      <p v-if="error" class="mt-4 rounded-md bg-acento-100 px-4 py-3 text-[14px] text-acento-800">
        {{ error }}
      </p>

      <AppButton
        tamano="lg"
        icono="check"
        bloque
        class="mt-6"
        :deshabilitado="enviando || !atribuidoA || cantidad <= 0"
        @click="confirmar"
      >
        Consumir {{ formateaCantidad(cantidad) }} · {{ nombreAtribuido }}
      </AppButton>

      <p
        v-if="!enLinea"
        class="mt-3 flex items-center justify-center gap-2 rounded-full border-[1.5px] border-dashed border-arena-300 px-4 py-2.5 text-[13px] text-arena-600"
      >
        <AppIcon name="sincronizar" :size="16" />
        Se guardará offline y se enviará solo
      </p>
    </div>
  </BottomSheet>
</template>
