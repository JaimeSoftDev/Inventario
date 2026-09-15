<script setup>
// registerType: 'prompt' -> no se activa la nueva versión sola a media
// acción del usuario; se le avisa y decide cuándo recargar.
import { onBeforeUnmount } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { useRegisterSW } from 'virtual:pwa-register/vue'

/**
 * Cada cuánto se pregunta al servidor si hay versión nueva. El navegador
 * solo lo hace al cargar la página de cero, y una PWA instalada se reanuda
 * en lugar de recargarse: sin esto puede pasar días sin enterarse de una
 * actualización.
 */
const INTERVALO = 60 * 60 * 1000

/** Reabrir la app varias veces seguidas no debe disparar una consulta cada vez. */
const ESPERA_MINIMA = 5 * 60 * 1000

let ultimaComprobacion = Date.now()
let temporizador = null
let limpiar = () => {}

const { needRefresh, updateServiceWorker } = useRegisterSW({
  immediate: true,
  onRegisteredSW(urlSW, registro) {
    // En desarrollo no hay service worker que actualizar.
    if (!registro) return

    async function comprueba({ forzar = false } = {}) {
      if (!navigator.onLine) return
      if (!forzar && Date.now() - ultimaComprobacion < ESPERA_MINIMA) return

      ultimaComprobacion = Date.now()

      try {
        // Se mira primero si el servidor responde. Llamar a update() con la
        // red caída o con el hosting devolviendo un error deja al registro
        // en un estado del que no se sale hasta recargar.
        const respuesta = await fetch(urlSW, {
          cache: 'no-store',
          headers: { 'cache-control': 'no-cache' },
        })
        if (respuesta?.status === 200) await registro.update()
      } catch {
        // Sin red o servidor caído: se reintenta en la siguiente ocasión.
      }
    }

    function alVolverAPrimerPlano() {
      if (document.visibilityState === 'visible') comprueba()
    }

    temporizador = setInterval(() => comprueba({ forzar: true }), INTERVALO)
    document.addEventListener('visibilitychange', alVolverAPrimerPlano)
    // Al recuperar la conexión conviene mirar: es justo cuando el móvil
    // vuelve de estar sin cobertura y puede llevar tiempo desactualizado.
    window.addEventListener('online', alVolverAPrimerPlano)

    limpiar = () => {
      document.removeEventListener('visibilitychange', alVolverAPrimerPlano)
      window.removeEventListener('online', alVolverAPrimerPlano)
    }
  },
})

onBeforeUnmount(() => {
  if (temporizador) clearInterval(temporizador)
  limpiar()
})

function actualizar() {
  updateServiceWorker(true)
}

function descartar() {
  needRefresh.value = false
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-6 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="translate-y-6 opacity-0"
    >
      <!-- Mismo lenguaje que el aviso de consumo (AppToast): píldora oscura
           sobre la barra inferior. Va algo más arriba para no taparlo si
           los dos coinciden. -->
      <div
        v-if="needRefresh"
        class="fixed inset-x-0 bottom-32 z-40 mx-auto w-max max-w-[92vw] px-4"
        role="status"
      >
        <div class="flex items-center gap-3 rounded-full bg-tinta py-2 pr-2 pl-4 text-fondo shadow-lg">
          <span class="text-[14px] font-bold">Hay una versión nueva</span>
          <button
            type="button"
            class="flex items-center gap-1.5 rounded-full px-3 py-2 text-[14px] font-bold text-acento-300"
            @click="actualizar"
          >
            <AppIcon name="sincronizar" :size="16" />
            Actualizar
          </button>
          <button
            type="button"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-fondo/60"
            aria-label="Ahora no"
            @click="descartar"
          >
            <AppIcon name="cerrar" :size="16" />
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
