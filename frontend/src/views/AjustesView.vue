<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import EstadoChip from '@/components/EstadoChip.vue'
import MemberChip from '@/components/MemberChip.vue'
import { useColaOffline } from '@/composables/useColaOffline'
import { useConexion } from '@/composables/useConexion'
import { useAuthStore } from '@/stores/auth'
import { useCatalogosStore } from '@/stores/catalogos'
import { useUsuariosStore } from '@/stores/usuarios'

const router = useRouter()
const auth = useAuthStore()
const usuariosStore = useUsuariosStore()
const catalogos = useCatalogosStore()
const { enLinea } = useConexion()
const { pendientes, conflictos } = useColaOffline()

onMounted(() => {
  usuariosStore.cargar()
  catalogos.cargarUbicaciones()
})

const otros = computed(() =>
  usuariosStore.usuarios.filter((usuario) => usuario.id !== auth.usuario?.id),
)

async function salir() {
  await auth.logout()
  router.replace({ name: 'login' })
}
</script>

<template>
  <div class="mx-auto w-full max-w-[460px] px-5 pt-4 pb-8">
    <h1 class="text-[34px] leading-none">Ajustes</h1>

    <section class="mt-6 flex items-center gap-4 rounded-lg bg-tarjeta p-4 shadow-sm">
      <MemberChip :usuario="auth.usuario" tamano="lg" />
      <div class="min-w-0 flex-1">
        <p class="truncate text-[17px] font-bold">{{ auth.usuario?.name }}</p>
        <p class="truncate text-[13px] text-arena-500">{{ auth.usuario?.email }}</p>
      </div>
    </section>

    <section class="mt-6">
      <h2 class="etiqueta-seccion">Atribución</h2>
      <div class="mt-2.5 rounded-md bg-tarjeta p-4 shadow-sm">
        <EstadoChip
          :tono="auth.usuario?.puede_atribuir_a_otros ? 'oliva' : 'neutro'"
          :icono="auth.usuario?.puede_atribuir_a_otros ? 'check' : 'personas'"
          :texto="
            auth.usuario?.puede_atribuir_a_otros
              ? 'Puedes registrar a nombre de otros'
              : 'Solo puedes registrar a tu nombre'
          "
        />
        <p class="mt-3 text-[14px] text-arena-600">
          Quien ejecuta la acción siempre queda registrado, aunque el movimiento se atribuya a otra
          persona del hogar.
        </p>
      </div>
    </section>

    <section class="mt-6">
      <h2 class="etiqueta-seccion">Hogar · {{ usuariosStore.usuarios.length }}</h2>
      <div class="mt-2.5 flex flex-wrap gap-3 rounded-md bg-tarjeta p-4 shadow-sm">
        <div v-for="usuario in otros" :key="usuario.id" class="flex w-16 flex-col items-center gap-1.5">
          <MemberChip :usuario="usuario" tamano="lg" />
          <span class="max-w-full truncate text-[12px] text-arena-600">{{ usuario.name }}</span>
        </div>
        <p v-if="!otros.length" class="text-[14px] text-arena-500">
          Todavía no hay nadie más en el hogar.
        </p>
      </div>
    </section>

    <section class="mt-6">
      <h2 class="etiqueta-seccion">Ubicaciones · {{ catalogos.ubicaciones.length }}</h2>
      <div class="mt-2.5 flex flex-wrap gap-2">
        <span
          v-for="ubicacion in catalogos.ubicaciones"
          :key="ubicacion.id"
          class="inline-flex h-10 items-center gap-1.5 rounded-full border border-arena-300 px-3.5 text-[14px] font-bold text-arena-700"
        >
          <AppIcon name="ubicacion" :size="15" />
          {{ ubicacion.nombre }}
        </span>
      </div>
    </section>

    <section class="mt-6">
      <h2 class="etiqueta-seccion">Sincronización</h2>
      <button
        type="button"
        class="mt-2.5 flex w-full items-center gap-3 rounded-md bg-tarjeta p-4 text-left shadow-sm"
        @click="router.push({ name: 'cola' })"
      >
        <span
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
          :class="conflictos.length ? 'bg-acento-100 text-acento-700' : 'bg-oliva-100 text-oliva-700'"
        >
          <AppIcon :name="enLinea ? 'sincronizar' : 'sinConexion'" :size="20" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-[15px] font-bold">
            {{ enLinea ? 'En línea' : 'Sin conexión' }}
          </span>
          <span class="block text-[13px] text-arena-500">
            {{ pendientes.length }} pendientes · {{ conflictos.length }} por revisar
          </span>
        </span>
        <AppIcon name="atras" :size="18" class="shrink-0 rotate-180 text-arena-400" />
      </button>
    </section>

    <AppButton variante="secundario" tamano="md" fuente="sans" icono="salir" bloque class="mt-8" @click="salir">
      Cerrar sesión
    </AppButton>
  </div>
</template>
