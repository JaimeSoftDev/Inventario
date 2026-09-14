<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import ActualizacionDisponible from '@/components/ActualizacionDisponible.vue'
import AppToast from '@/components/AppToast.vue'
import BottomNav from '@/components/BottomNav.vue'
import SplashArranque from '@/components/SplashArranque.vue'
import { useConexion } from '@/composables/useConexion'
import { db } from '@/db/dexie'

const route = useRoute()
const { enLinea } = useConexion()

const arrancando = ref(true)
const fechaDatos = ref(null)

onMounted(async () => {
  try {
    // Abrir IndexedDB es lo único que bloquea el primer pintado: hasta que
    // no está lista no sabemos si hay datos cacheados que mostrar sin red.
    await db.open()
    const cacheados = await db.productos_cache.count()
    if (cacheados > 0) {
      fechaDatos.value = new Date().toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
    }
  } catch {
    // Sin IndexedDB (ventana privada) la app sigue funcionando con red.
  } finally {
    arrancando.value = false
  }
})

const mostrarNav = computed(() => route.meta.nav !== false)
</script>

<template>
  <SplashArranque v-if="arrancando" :en-linea="enLinea" :fecha-datos="fechaDatos" />

  <div v-else class="flex min-h-dvh flex-col">
    <main class="flex-1">
      <RouterView />
    </main>

    <BottomNav v-if="mostrarNav" />
  </div>

  <AppToast />
  <ActualizacionDisponible />
</template>
