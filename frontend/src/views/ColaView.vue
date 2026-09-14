<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import MemberChip from '@/components/MemberChip.vue'
import { useColaOffline } from '@/composables/useColaOffline'
import { useConexion } from '@/composables/useConexion'
import { formateaCantidad } from '@/composables/useEstadoProducto'
import { sincronizando, sincronizar } from '@/composables/useSincronizador'
import { mostrarToast } from '@/composables/useToast'
import { useProductosStore } from '@/stores/productos'
import { useUsuariosStore } from '@/stores/usuarios'

/**
 * Cola de sincronización.
 *
 * Separa lo que solo espera red (pendiente, se resuelve solo) de lo que
 * necesita una decisión humana (conflicto): al volver la conexión el
 * contexto puede haber cambiado —otra persona consumió ese lote— y
 * reintentar a ciegas descuadraría el stock.
 */
const router = useRouter()
const { pendientes, conflictos, descartar, reencolar } = useColaOffline()
const { enLinea } = useConexion()
const productosStore = useProductosStore()
const usuariosStore = useUsuariosStore()

const TIPOS = { compra: 'Compra', consumo: 'Consumo', correccion: 'Corrección' }

function nombreProducto(id) {
  return productosStore.productos.find((producto) => producto.id === id)?.nombre ?? 'Producto'
}

function usuario(id) {
  return usuariosStore.usuarios.find((item) => item.id === id) ?? null
}

function signo(tipo) {
  if (tipo === 'compra') return '+'
  if (tipo === 'consumo') return '−'
  return '='
}

function hora(marca) {
  return new Date(marca).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
}

const titulo = computed(() => (enLinea.value ? 'Sincronizando' : 'Sin conexión'))
const subtitulo = computed(() =>
  enLinea.value
    ? 'Enviando lo que quedó guardado en el móvil.'
    : 'Todo se guarda en el móvil y se envía solo.',
)

async function reintentar() {
  await sincronizar()
  await productosStore.cargar()
}

async function aplicarIgualmente(item) {
  await reencolar(item.id)
  await reintentar()
  mostrarToast({ texto: 'Reintentado sobre el stock actual' })
}
</script>

<template>
  <div class="mx-auto w-full max-w-[460px] px-5 pt-4 pb-8">
    <header class="flex items-center gap-4">
      <span
        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-[1.5px] border-dashed border-arena-400 text-arena-600"
      >
        <AppIcon :name="enLinea ? 'sincronizar' : 'sinConexion'" :size="26" />
      </span>
      <div class="min-w-0">
        <h1 class="text-[30px] leading-none">{{ titulo }}</h1>
        <p class="mt-1.5 text-[14px] text-arena-500">{{ subtitulo }}</p>
      </div>
    </header>

    <!-- Conflictos primero: son los únicos que no se resuelven solos. -->
    <section v-if="conflictos.length" class="mt-7">
      <h2 class="etiqueta-seccion">Necesita revisión · {{ conflictos.length }}</h2>

      <div class="mt-2.5 flex flex-col gap-3">
        <article
          v-for="item in conflictos"
          :key="item.id"
          class="rounded-md bg-acento-700 p-4 text-fondo"
        >
          <p class="flex items-center gap-2 text-[17px] font-bold">
            <AppIcon name="aviso" :size="20" />
            <span class="font-display">
              {{ signo(item.tipo) }}{{ formateaCantidad(item.payload.cantidad) }}
            </span>
            {{ nombreProducto(item.payload.producto_id) }}
            <span v-if="usuario(item.payload.usuario_atribuido_id)" class="font-normal">
              · {{ usuario(item.payload.usuario_atribuido_id).name }}
            </span>
          </p>

          <p class="mt-2 text-[14px] text-acento-100">
            {{ item.error_mensaje }}
          </p>

          <div class="mt-4 flex flex-col gap-2">
            <AppButton variante="claro" tamano="md" bloque @click="aplicarIgualmente(item)">
              Aplicar sobre el stock actual
            </AppButton>
            <div class="flex gap-2">
              <AppButton
                variante="contorno-claro"
                tamano="sm"
                fuente="sans"
                class="flex-1"
                @click="router.push({ name: 'producto', params: { id: item.payload.producto_id } })"
              >
                Ver producto
              </AppButton>
              <AppButton
                variante="contorno-claro"
                tamano="sm"
                fuente="sans"
                class="flex-1"
                @click="descartar(item.id)"
              >
                Descartar
              </AppButton>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section v-if="pendientes.length" class="mt-7">
      <h2 class="etiqueta-seccion">Pendientes · {{ pendientes.length }}</h2>

      <div class="mt-2.5 flex flex-col gap-2.5">
        <article
          v-for="item in pendientes"
          :key="item.id"
          class="flex items-center gap-3 rounded-md border-[1.5px] border-dashed border-arena-300 p-3.5"
        >
          <MemberChip :usuario="usuario(item.payload.usuario_atribuido_id)" tamano="md" />

          <div class="min-w-0 flex-1">
            <p class="text-[15px] font-bold">
              <span class="font-display">
                {{ signo(item.tipo) }}{{ formateaCantidad(item.payload.cantidad) }}
              </span>
              {{ nombreProducto(item.payload.producto_id) }}
            </p>
            <p class="mt-0.5 text-[13px] text-arena-500">
              {{ TIPOS[item.tipo] }} · {{ hora(item.creado_en) }}
            </p>
          </div>

          <span class="flex shrink-0 items-center gap-1.5 text-[13px] font-bold text-arena-500">
            <AppIcon name="sincronizar" :size="15" />
            En cola
          </span>
        </article>
      </div>
    </section>

    <p v-if="pendientes.length" class="mt-5 text-center text-[14px] text-arena-500">
      Se reintenta automáticamente al volver la conexión. Nada se pierde si cierras la app.
    </p>

    <section
      v-if="!pendientes.length && !conflictos.length"
      class="mt-12 flex flex-col items-center text-center"
    >
      <span class="flex h-20 w-20 items-center justify-center rounded-full bg-oliva-100 text-oliva-600">
        <AppIcon name="check" :size="34" />
      </span>
      <p class="mt-5 text-[17px] font-bold">Todo sincronizado</p>
      <p class="mt-1.5 text-[14px] text-arena-500">No queda nada por enviar.</p>
      <AppButton variante="secundario" tamano="md" fuente="sans" class="mt-6" @click="router.push({ name: 'stock' })">
        Volver al inventario
      </AppButton>
    </section>

    <AppButton
      v-if="pendientes.length || conflictos.length"
      tamano="lg"
      icono="sincronizar"
      bloque
      class="mt-6"
      :deshabilitado="sincronizando || !enLinea"
      @click="reintentar"
    >
      {{ sincronizando ? 'Sincronizando…' : 'Reintentar ahora' }}
    </AppButton>
  </div>
</template>
