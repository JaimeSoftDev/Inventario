<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '@/api/client'
import FiltroChips from '@/components/FiltroChips.vue'
import MovementRow from '@/components/MovementRow.vue'
import { useColaOffline } from '@/composables/useColaOffline'
import { ultimaSincronizacion } from '@/composables/useSincronizador'
import { useProductosStore } from '@/stores/productos'

/**
 * Histórico de movimientos, agrupado por día. Mezcla lo confirmado por el
 * servidor con lo que aún está en la cola local, porque para quien mira la
 * pantalla ambas cosas ya "han pasado" en casa.
 */
const router = useRouter()
const productosStore = useProductosStore()
const { pendientes, conflictos, descartar } = useColaOffline()

const movimientos = ref([])
const cargando = ref(true)
const error = ref(null)
const filtro = ref(null)

const OPCIONES = [
  { valor: null, etiqueta: 'Todo' },
  { valor: 'consumo', etiqueta: 'Consumos' },
  { valor: 'compra', etiqueta: 'Compras' },
  { valor: 'correccion', etiqueta: 'Correcciones' },
]

onMounted(cargar)
watch([filtro, ultimaSincronizacion], cargar)

async function cargar() {
  cargando.value = true
  error.value = null

  try {
    const { data } = await apiClient.get('/movimientos', {
      params: { tipo: filtro.value ?? undefined, por_pagina: 50 },
    })
    movimientos.value = data.data
  } catch {
    error.value = 'No se pudo cargar el histórico (sin conexión).'
  } finally {
    cargando.value = false
  }
}

function nombreProducto(productoId) {
  return (
    productosStore.productos.find((producto) => producto.id === productoId)?.nombre ?? 'Producto'
  )
}

/** Los ítems de la cola se presentan con la misma forma que los del servidor. */
function comoMovimiento(item) {
  return {
    id: `cola-${item.id}`,
    tipo: item.tipo,
    cantidad: item.payload.cantidad ?? 0,
    producto: { id: item.payload.producto_id, nombre: nombreProducto(item.payload.producto_id) },
    atribuido_a: null,
    registrado_por: null,
    nota: item.payload.nota ?? null,
    // Una compra encolada ya sabe lo que costó: el importe se ve igual
    // que si estuviera sincronizada.
    precio_unitario: item.payload.precio_unitario ?? null,
    created_at: new Date(item.creado_en).toISOString(),
  }
}

const locales = computed(() => {
  const items = [...conflictos.value, ...pendientes.value]
  return items
    .filter((item) => !filtro.value || item.tipo === filtro.value)
    .map((item) => ({
      clave: `cola-${item.id}`,
      movimiento: comoMovimiento(item),
      estado: item.estado === 'error' ? 'error' : 'pendiente',
      mensajeError: item.error_mensaje,
      colaId: item.id,
    }))
})

const remotos = computed(() =>
  movimientos.value.map((movimiento) => ({
    clave: `api-${movimiento.id}`,
    movimiento,
    estado: 'sincronizado',
    mensajeError: null,
    colaId: null,
  })),
)

/** Agrupa por día con etiquetas humanas (hoy / ayer / fecha). */
const porDia = computed(() => {
  const todos = [...locales.value, ...remotos.value].sort(
    (a, b) => new Date(b.movimiento.created_at) - new Date(a.movimiento.created_at),
  )

  const grupos = new Map()
  for (const fila of todos) {
    const etiqueta = etiquetaDeDia(fila.movimiento.created_at)
    if (!grupos.has(etiqueta)) grupos.set(etiqueta, [])
    grupos.get(etiqueta).push(fila)
  }
  return [...grupos.entries()].map(([etiqueta, filas]) => ({ etiqueta, filas }))
})

function etiquetaDeDia(fecha) {
  const dia = new Date(fecha)
  const hoy = new Date()
  const ayer = new Date()
  ayer.setDate(hoy.getDate() - 1)

  const mismaFecha = (a, b) => a.toDateString() === b.toDateString()
  if (mismaFecha(dia, hoy)) return 'Hoy'
  if (mismaFecha(dia, ayer)) return 'Ayer'

  return dia.toLocaleDateString('es-ES', { day: 'numeric', month: 'long' })
}
</script>

<template>
  <div class="mx-auto w-full max-w-[460px] px-5 pt-4 pb-8">
    <h1 class="text-[34px] leading-none">Histórico</h1>

    <div class="mt-5">
      <FiltroChips v-model="filtro" :opciones="OPCIONES" />
    </div>

    <p v-if="cargando && !movimientos.length" class="mt-8 text-center text-[15px] text-arena-500">
      Cargando…
    </p>
    <p v-else-if="error && !locales.length" class="mt-8 text-center text-[15px] text-arena-500">
      {{ error }}
    </p>

    <section v-for="grupo in porDia" :key="grupo.etiqueta" class="mt-6">
      <h2 class="etiqueta-seccion">{{ grupo.etiqueta }}</h2>
      <div class="mt-2.5 flex flex-col gap-2.5">
        <MovementRow
          v-for="fila in grupo.filas"
          :key="fila.clave"
          :movimiento="fila.movimiento"
          :estado="fila.estado"
          :mensaje-error="fila.mensajeError"
          @revisar="router.push({ name: 'cola' })"
          @descartar="descartar(fila.colaId)"
        />
      </div>
    </section>

    <p
      v-if="!cargando && !porDia.length"
      class="mt-10 text-center text-[15px] text-arena-500"
    >
      Todavía no hay movimientos registrados.
    </p>
  </div>
</template>
