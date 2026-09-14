import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

/**
 * Cliente HTTP central. La autenticación es por bearer token (Sanctum
 * personal access tokens), no por cookies de sesión: cada petición añade
 * el header Authorization si hay un token guardado.
 */
export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: {
    Accept: 'application/json',
  },
})

apiClient.interceptors.request.use((config) => {
  const auth = useAuthStore()
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const auth = useAuthStore()
      auth.cerrarSesionLocal()
    }
    return Promise.reject(error)
  },
)

/**
 * Distingue un fallo de red/servidor caído (sin respuesta HTTP) de un
 * fallo de negocio (422/403 con respuesta del servidor). Los primeros deben
 * encolarse para reintentar offline; los segundos no.
 */
export function esErrorDeRed(error) {
  return !error.response
}

export default apiClient
