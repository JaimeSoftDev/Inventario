<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AnadirStockSheet from '@/components/AnadirStockSheet.vue'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import ConsumoSheet from '@/components/ConsumoSheet.vue'
import EstadoChip from '@/components/EstadoChip.vue'
import MemberChip from '@/components/MemberChip.vue'
import MovementRow from '@/components/MovementRow.vue'
import StatusStripe from '@/components/StatusStripe.vue'
import apiClient from '@/api/client'
import { db } from '@/db/dexie'
import { useColaOffline } from '@/composables/useColaOffline'
import { diasHasta, estadoDe, formateaCantidad, formateaDias, unidadPara } from '@/composables/useEstadoProducto'
import { useProductosStore } from '@/stores/productos'

/**
 * Ficha de producto: el stock total no basta para decidir, porque está
 * repartido en lotes con caducidades distintas. Aquí se ven los lotes tal
 * como los ordenará FEFO al consumir.
 */
const props = defineProps({
  id: { type: [String, Number], required: true },
})

const router = useRouter()
const productosStore = useProductosStore()
const { pendientesPorProducto } = useColaOffline()

const producto = ref(null)
const lotes = ref([])
const movimientos = ref([])
const cargando = ref(true)
const desdeCache = ref(false)
const hojaConsumo = ref(null)
const loteEnConsumo = ref(null)
const hojaAnadir = ref(null)

onMounted(cargar)

async function cargar() {
  cargando.value = true
  desdeCache.value = false

  try {
    const [detalle, stock, historial] = await Promise.all([
      apiClient.get(`/productos/${props.id}`),
      apiClient.get(`/productos/${props.id}/stock`),
      apiClient.get('/movimientos', { params: { producto_id: props.id, por_pagina: 5 } }),
    ])

    producto.value = detalle.data.data
    lotes.value = stock.data.entradas
    movimientos.value = historial.data.data

    await db.entradas_stock_cache.where('producto_id').equals(Number(props.id)).delete()
    await db.entradas_stock_cache.bulkPut(stock.data.entradas)
  } catch {
    // Sin red: se sirve lo último cacheado, avisando de que puede no estar
    // al día en vez de fingir que sí lo está.
    producto.value =
      productosStore.productos.find((item) => item.id === Number(props.id)) ??
      (await db.productos_cache.get(Number(props.id))) ??
      null
    lotes.value = await db.entradas_stock_cache.where('producto_id').equals(Number(props.id)).toArray()
    desdeCache.value = true
  } finally {
    cargando.value = false
  }
}

const estado = computed(() => (producto.value ? estadoDe(producto.value) : null))
const unidad = computed(() => producto.value?.unidad_medida?.abreviatura ?? 'uds')
const pendientes = computed(() => pendientesPorProducto.value[Number(props.id)] ?? 0)

const subtitulo = computed(() => {
  if (!producto.value) return ''
  const partes = [producto.value.ubicacion_por_defecto?.nombre, producto.value.descripcion]
  if (Number(producto.value.stock_minimo) > 0) {
    partes.push(`mínimo ${formateaCantidad(producto.value.stock_minimo)}`)
  }
  return partes.filter(Boolean).join(' · ')
})

const proximaCaducidad = computed(() => diasHasta(producto.value?.proxima_caducidad))

const faltanParaMinimo = computed(() => {
  if (!producto.value) return 0
  const minimo = Number(producto.value.stock_minimo ?? 0)
  const actual = Number(producto.value.stock_actual ?? 0)
  return Math.max(0, minimo - actual)
})

function estadoLote(lote) {
  const dias = diasHasta(lote.fecha_caducidad)
  if (dias === null) return { tono: 'neutro', icono: null, texto: 'Sin caducidad' }
  if (dias <= 3) {
    return {
      tono: 'acento',
      icono: 'reloj',
      texto: `Caduca ${formateaFechaCorta(lote.fecha_caducidad)} · ${formateaDias(dias)}`,
    }
  }
  return {
    tono: 'oliva',
    icono: null,
    texto: `Caduca ${formateaFechaCorta(lote.fecha_caducidad)} · ${formateaDias(dias)}`,
  }
}

function formateaFechaCorta(fecha) {
  return new Date(fecha).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

function alRegistrar() {
  cargar()
  productosStore.cargar()
}
</script>

<template>
  <div class="mx-auto w-full max-w-[460px] px-5 pt-4 pb-8">
    <header class="flex items-center justify-between gap-3">
      <button
        type="button"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-arena-300 text-arena-700"
        aria-label="Volver"
        @click="router.back()"
      >
        <AppIcon name="atras" :size="20" />
      </button>

      <EstadoChip
        v-if="pendientes > 0"
        tono="pendiente"
        icono="sincronizar"
        :texto="`${pendientes} pendientes`"
      />
    </header>

    <p v-if="cargando" class="mt-10 text-center text-[15px] text-arena-500">Cargando…</p>

    <template v-else-if="producto">
      <h1 class="mt-5 text-[34px] leading-tight">{{ producto.nombre }}</h1>
      <p class="mt-1.5 text-[14px] text-arena-500">{{ subtitulo }}</p>

      <p v-if="desdeCache" class="mt-3 text-[13px] text-arena-500">
        Mostrando datos guardados en el móvil.
      </p>

      <!-- Resumen: cuánto queda y qué es lo primero que caduca. -->
      <section
        class="mt-5 flex items-center gap-4 rounded-lg p-5"
        :class="estado.requiereAccion ? 'bg-acento-100' : 'bg-oliva-100'"
      >
        <div class="min-w-0 flex-1">
          <p class="flex items-baseline gap-2">
            <span class="font-display text-[44px] leading-none">
              {{ formateaCantidad(producto.stock_actual) }}
            </span>
            <span class="text-[15px] font-bold text-arena-600">{{ unidadPara(producto.stock_actual, unidad) }}</span>
          </p>

          <EstadoChip
            v-if="faltanParaMinimo > 0"
            tono="bajo"
            icono="flechaBajaDiag"
            :texto="`${formateaCantidad(faltanParaMinimo)} por debajo del mínimo`"
            class="mt-3"
          />
          <EstadoChip
            v-else
            tono="oliva"
            icono="check"
            texto="Por encima del mínimo"
            class="mt-3"
          />
        </div>

        <div
          v-if="proximaCaducidad !== null"
          class="flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-full bg-fondo text-center"
        >
          <span class="font-display text-[20px] leading-none">
            {{ formateaDias(proximaCaducidad) }}
          </span>
          <span class="mt-1 px-2 text-[9px] font-extrabold tracking-wide text-arena-500 uppercase">
            lote + próx.
          </span>
        </div>
      </section>

      <div class="mt-4 grid grid-cols-2 gap-3">
        <AppButton
          tamano="md"
          icono="flechaAbajo"
          :deshabilitado="Number(producto.stock_actual) <= 0"
          @click="((loteEnConsumo = null), (hojaConsumo = producto))"
        >
          Consumir
        </AppButton>
        <AppButton variante="secundario" tamano="md" icono="flechaArriba" @click="hojaAnadir = producto">
          Añadir
        </AppButton>
      </div>

      <section class="mt-7">
        <h2 class="etiqueta-seccion">Lotes · {{ lotes.length }}</h2>

        <div class="mt-2.5 flex flex-col gap-2.5">
          <article
            v-for="lote in lotes"
            :key="lote.id"
            class="relative rounded-md bg-tarjeta py-3.5 pr-4 pl-4 shadow-sm"
          >
            <StatusStripe :tono="estadoLote(lote).tono" />

            <div class="flex items-start justify-between gap-3">
              <p class="flex items-baseline gap-1.5">
                <span class="font-display text-[22px] leading-none">
                  {{ formateaCantidad(lote.cantidad_restante) }}
                </span>
                <span class="text-[13px] text-arena-600">{{ unidadPara(lote.cantidad_restante, unidad) }}</span>
              </p>

              <EstadoChip
                :tono="estadoLote(lote).tono"
                :icono="estadoLote(lote).icono"
                :texto="estadoLote(lote).texto"
                tamano="sm"
              />
            </div>

            <div class="mt-2.5 flex items-center justify-between gap-3">
              <p class="flex min-w-0 items-center gap-2 text-[13px] text-arena-500">
                <template v-if="lote.anadido_por">
                  <MemberChip
                    :usuario="{ id: lote.anadido_por.id, name: lote.anadido_por.nombre }"
                    tamano="sm"
                  />
                  <span class="truncate">
                    Añadido por {{ lote.anadido_por.nombre }} · {{ formateaFechaCorta(lote.fecha_compra) }}
                  </span>
                </template>
                <template v-else>
                  <AppIcon name="ubicacion" :size="14" />
                  {{ lote.ubicacion?.nombre }}
                </template>
                <span v-if="lote.abierto" class="shrink-0 font-bold text-acento-600">· abierto</span>
              </p>

              <button
                type="button"
                class="text-[14px] font-bold text-acento-600"
                @click="((loteEnConsumo = lote), (hojaConsumo = producto))"
              >
                Consumir
              </button>
            </div>
          </article>

          <p v-if="!lotes.length" class="rounded-md bg-arena-100 px-4 py-6 text-center text-[14px] text-arena-500">
            Sin stock. Añade una compra para empezar un lote nuevo.
          </p>
        </div>
      </section>

      <section v-if="movimientos.length" class="mt-7">
        <h2 class="etiqueta-seccion">Últimos movimientos</h2>
        <div class="mt-2.5 flex flex-col gap-2.5">
          <MovementRow
            v-for="movimiento in movimientos"
            :key="movimiento.id"
            :movimiento="movimiento"
          />
        </div>
      </section>
    </template>

    <p v-else class="mt-10 text-center text-[15px] text-arena-500">
      No se encontró el producto.
    </p>

    <ConsumoSheet
      :producto="hojaConsumo"
      :lote="loteEnConsumo"
      @cerrar="hojaConsumo = null"
      @registrado="alRegistrar"
    />
    <AnadirStockSheet :producto="hojaAnadir" @cerrar="hojaAnadir = null" @registrado="alRegistrar" />
  </div>
</template>
