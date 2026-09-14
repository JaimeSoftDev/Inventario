import { onScopeDispose, ref } from 'vue'

/**
 * Estado de conexión del dispositivo. Se comparte a nivel de módulo para
 * que todas las pantallas coincidan y solo haya un par de listeners.
 */
export const enLinea = ref(typeof navigator === 'undefined' ? true : navigator.onLine)

let suscriptores = 0

function actualizar() {
  enLinea.value = navigator.onLine
}

export function useConexion() {
  if (suscriptores === 0) {
    window.addEventListener('online', actualizar)
    window.addEventListener('offline', actualizar)
  }
  suscriptores += 1

  onScopeDispose(() => {
    suscriptores -= 1
    if (suscriptores === 0) {
      window.removeEventListener('online', actualizar)
      window.removeEventListener('offline', actualizar)
    }
  })

  return { enLinea }
}
