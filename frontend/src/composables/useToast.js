import { ref } from 'vue'

/**
 * Avisos efímeros con acción de deshacer. Estado a nivel de módulo (no de
 * componente) para que cualquier pantalla pueda lanzarlos y App.vue sea el
 * único que los pinta.
 */
export const toastActual = ref(null)

let temporizador = null
const DURACION_MS = 6000

export function mostrarToast({ texto, usuario = null, alDeshacer = null }) {
  clearTimeout(temporizador)

  toastActual.value = { texto, usuario, alDeshacer, id: Date.now() }
  temporizador = setTimeout(ocultarToast, DURACION_MS)
}

export function ocultarToast() {
  clearTimeout(temporizador)
  toastActual.value = null
}

export function useToast() {
  return { toastActual, mostrarToast, ocultarToast }
}
