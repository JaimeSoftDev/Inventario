<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import AnadirStockSheet from '@/components/AnadirStockSheet.vue'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import ConsumoSheet from '@/components/ConsumoSheet.vue'
import FiltroChips from '@/components/FiltroChips.vue'
import MemberChip from '@/components/MemberChip.vue'
import OfflineBanner from '@/components/OfflineBanner.vue'
import ProductCard from '@/components/ProductCard.vue'
import { useColaOffline } from '@/composables/useColaOffline'
import { estadoDe } from '@/composables/useEstadoProducto'
import { useMovimientoStock } from '@/composables/useMovimientoStock'
import { useConexion } from '@/composables/useConexion'
import { ultimaSincronizacion } from '@/composables/useSincronizador'
import { mostrarToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { useCatalogosStore } from '@/stores/catalogos'
import { useProductosStore } from '@/stores/productos'
import { useUsuariosStore } from '@/stores/usuarios'

const router = useRouter()
const auth = useAuthStore()
const productosStore = useProductosStore()
const catalogos = useCatalogosStore()
const usuariosStore = useUsuariosStore()
const { enLinea } = useConexion()
const { pendientes, conflictos, pendientesPorProducto } = useColaOffline()
const { registrarConsumo, deshacerConsumo } = useMovimientoStock()

const filtroUbicacion = ref(null)
const busqueda = ref('')
const buscando = ref(false)
const productoEnHoja = ref(null)
const productoParaAnadir = ref(null)

onMounted(() => {
  productosStore.cargar()
  catalogos.cargarTodos()
  usuariosStore.cargar()
})

// Al vaciarse la cola, el stock local ya no coincide con el servidor.
watch(ultimaSincronizacion, () => productosStore.cargar())

const ubicaciones = computed(() => {
  const nombres = new Set()
  for (const producto of productosStore.productos) {
    const nombre = producto.ubicacion_por_defecto?.nombre
    if (nombre) nombres.add(nombre)
  }
  return [...nombres].sort((a, b) => a.localeCompare(b, 'es'))
})

const opcionesFiltro = computed(() => [
  { valor: null, etiqueta: 'Todo' },
  ...ubicaciones.value.map((nombre) => ({ valor: nombre, etiqueta: nombre })),
])

const productosVisibles = computed(() => {
  const termino = busqueda.value.trim().toLowerCase()

  return productosStore.productos.filter((producto) => {
    const coincideUbicacion =
      !filtroUbicacion.value || producto.ubicacion_por_defecto?.nombre === filtroUbicacion.value
    const coincideBusqueda = !termino || producto.nombre.toLowerCase().includes(termino)
    return coincideUbicacion && coincideBusqueda
  })
})

/** Lo urgente primero, fuera de su ubicación: es lo que hay que decidir hoy. */
const requierenAccion = computed(() =>
  productosVisibles.value
    .filter((producto) => estadoDe(producto).requiereAccion)
    .sort((a, b) => {
      const prioridad = { caduca: 0, bajo: 1, ok: 2 }
      return prioridad[estadoDe(a).clave] - prioridad[estadoDe(b).clave]
    }),
)

const porUbicacion = computed(() => {
  const idsUrgentes = new Set(requierenAccion.value.map((producto) => producto.id))
  const grupos = new Map()

  for (const producto of productosVisibles.value) {
    if (idsUrgentes.has(producto.id)) continue

    const nombre = producto.ubicacion_por_defecto?.nombre ?? 'Sin ubicación'
    if (!grupos.has(nombre)) grupos.set(nombre, [])
    grupos.get(nombre).push(producto)
  }

  return [...grupos.entries()]
    .map(([nombre, productos]) => ({ nombre, productos }))
    .sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
})

const totalProductos = computed(() => productosStore.productos.length)

/** Aún no sabemos qué hay: ni inventario vacío ni lista, solo esperar. */
const hidratando = computed(
  () => productosStore.cargando && productosStore.productos.length === 0,
)
const inventarioVacio = computed(() => !hidratando.value && totalProductos.value === 0)
const hayFiltroActivo = computed(() => filtroUbicacion.value !== null || busqueda.value.trim() !== '')

async function consumirRapido(producto, cantidad) {
  const usuario = auth.usuario
  if (!usuario) return

  // Optimista: la fila baja al instante y la cola se encarga del resto.
  productosStore.ajustarStockLocal(producto.id, -cantidad)

  try {
    const resultado = await registrarConsumo({
      producto_id: producto.id,
      cantidad,
      usuario_atribuido_id: usuario.id,
    })

    mostrarToast({
      texto: `−${cantidad} ${producto.nombre} · ${usuario.name}`,
      usuario,
      alDeshacer: () => deshacer(producto, cantidad, resultado),
    })
  } catch (error) {
    // Regla de negocio (sin stock, atribución no permitida): se revierte lo
    // que habíamos pintado y se dice por qué.
    productosStore.ajustarStockLocal(producto.id, cantidad)
    mostrarToast({ texto: error.response?.data?.message ?? 'No se pudo registrar el consumo' })
  }
}

async function deshacer(producto, cantidad, resultado) {
  productosStore.ajustarStockLocal(producto.id, cantidad)

  try {
    await deshacerConsumo(resultado)
  } finally {
    await productosStore.cargar()
  }
}

function abrirFicha(producto) {
  router.push({ name: 'producto', params: { id: producto.id } })
}
</script>

<template>
  <div class="flex min-h-dvh flex-col">
    <div class="mx-auto w-full max-w-[460px] flex-1 px-5 pt-4 pb-6">
      <header class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <h1 class="text-[34px] leading-none">Inventario</h1>
          <p v-if="!hidratando" class="mt-2 text-[13px] text-arena-500">
            {{ totalProductos }} productos · {{ ubicaciones.length }} ubicaciones
          </p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <button
            type="button"
            class="flex h-11 w-11 items-center justify-center rounded-full border border-arena-300 text-arena-700"
            aria-label="Buscar producto"
            @click="buscando = !buscando"
          >
            <AppIcon name="buscar" :size="20" />
          </button>
          <RouterLink :to="{ name: 'ajustes' }" aria-label="Tu perfil">
            <MemberChip :usuario="auth.usuario" tamano="lg" class="!h-11 !w-11 !text-[15px]" />
          </RouterLink>
        </div>
      </header>

      <input
        v-if="buscando"
        v-model="busqueda"
        type="search"
        placeholder="Buscar en el inventario…"
        class="mt-3 w-full rounded-full border border-arena-300 bg-arena-50 px-4 py-3 outline-none placeholder:text-arena-400 focus:border-acento-400"
      />

      <div class="mt-3">
        <OfflineBanner
          :en-linea="enLinea"
          :pendientes="pendientes.length"
          :conflictos="conflictos.length"
          @ver="router.push({ name: 'cola' })"
        />
      </div>

      <div v-if="!inventarioVacio && !hidratando" class="mt-4">
        <FiltroChips v-model="filtroUbicacion" :opciones="opcionesFiltro" />
      </div>

      <!-- Arranque: aún leyendo la caché local. No se afirma que no haya
           nada hasta saberlo, que es justo lo contrario de lo que el
           usuario necesita ver al abrir la app sin conexión. -->
      <div v-if="hidratando" class="mt-8 flex flex-col gap-2.5" aria-busy="true">
        <span
          v-for="n in 4"
          :key="n"
          class="h-[74px] animate-pulse rounded-md bg-arena-200/70"
        />
      </div>

      <!-- Estado vacío: un único camino primario y tres atajos. -->
      <section v-if="inventarioVacio" class="flex flex-col items-center px-2 pt-14 text-center">
        <span
          class="flex h-28 w-28 items-center justify-center rounded-full border-[1.5px] border-dashed border-arena-300 text-arena-400"
        >
          <AppIcon name="caja" :size="44" />
        </span>
        <h2 class="mt-7 text-[27px] leading-tight">Aún no hay nada<br />en el inventario</h2>
        <p class="mt-3 max-w-[30ch] text-[15px] text-arena-600">
          Añade lo primero que tengas a mano. No hace falta cargar toda la casa de golpe.
        </p>

        <AppButton
          tamano="lg"
          icono="mas"
          class="mt-8"
          @click="router.push({ name: 'producto.nuevo' })"
        >
          Añadir primer producto
        </AppButton>

        <div class="mt-4 flex flex-wrap justify-center gap-2">
          <AppButton variante="secundario" tamano="sm" fuente="sans" @click="router.push({ name: 'ajustes' })">
            Crear ubicaciones
          </AppButton>
          <AppButton variante="secundario" tamano="sm" fuente="sans" @click="router.push({ name: 'ajustes' })">
            Invitar al hogar
          </AppButton>
        </div>
      </section>

      <template v-else-if="!hidratando">
        <section v-if="requierenAccion.length" class="mt-6">
          <h2 class="etiqueta-seccion">Requiere acción · {{ requierenAccion.length }}</h2>
          <div class="mt-2.5 flex flex-col gap-2.5">
            <ProductCard
              v-for="producto in requierenAccion"
              :key="producto.id"
              :producto="producto"
              :usuario-actual="auth.usuario"
              :pendientes="pendientesPorProducto[producto.id] ?? 0"
              @consumir="consumirRapido(producto, $event)"
              @anadir="productoParaAnadir = producto"
              @abrir="abrirFicha(producto)"
              @abrir-hoja="productoEnHoja = producto"
            />
          </div>
        </section>

        <section v-for="grupo in porUbicacion" :key="grupo.nombre" class="mt-6">
          <h2 class="etiqueta-seccion">{{ grupo.nombre }} · {{ grupo.productos.length }}</h2>
          <div class="mt-2.5 flex flex-col gap-2.5">
            <ProductCard
              v-for="producto in grupo.productos"
              :key="producto.id"
              :producto="producto"
              :usuario-actual="auth.usuario"
              :pendientes="pendientesPorProducto[producto.id] ?? 0"
              @consumir="consumirRapido(producto, $event)"
              @anadir="productoParaAnadir = producto"
              @abrir="abrirFicha(producto)"
              @abrir-hoja="productoEnHoja = producto"
            />
          </div>
        </section>

        <p
          v-if="!requierenAccion.length && !porUbicacion.length && hayFiltroActivo"
          class="mt-10 text-center text-[15px] text-arena-500"
        >
          Nada coincide con ese filtro.
        </p>

        <AppButton
          variante="secundario"
          tamano="md"
          fuente="sans"
          icono="mas"
          bloque
          class="mt-6"
          @click="router.push({ name: 'producto.nuevo' })"
        >
          Nuevo producto
        </AppButton>
      </template>
    </div>

    <ConsumoSheet
      :producto="productoEnHoja"
      @cerrar="productoEnHoja = null"
      @registrado="productosStore.cargar()"
    />
    <AnadirStockSheet
      :producto="productoParaAnadir"
      @cerrar="productoParaAnadir = null"
      @registrado="productosStore.cargar()"
    />
  </div>
</template>
