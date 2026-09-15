import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import apiClient from '@/api/client'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('auth_token') ?? null)
  const usuario = ref(JSON.parse(localStorage.getItem('auth_usuario') ?? 'null'))

  const estaAutenticado = computed(() => !!token.value)

  function guardarSesion(nuevoToken, nuevoUsuario) {
    token.value = nuevoToken
    usuario.value = nuevoUsuario
    localStorage.setItem('auth_token', nuevoToken)
    localStorage.setItem('auth_usuario', JSON.stringify(nuevoUsuario))
  }

  /** Limpia el estado local sin llamar a la API (usado tras un 401). */
  function cerrarSesionLocal() {
    token.value = null
    usuario.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_usuario')
  }

  /** `identificador` es el nombre del miembro o su correo, indistintamente. */
  async function login(identificador, password) {
    const { data } = await apiClient.post('/login', {
      identificador,
      password,
      device_name: 'pwa',
    })
    guardarSesion(data.token, data.usuario)
  }

  async function logout() {
    try {
      await apiClient.post('/logout')
    } finally {
      cerrarSesionLocal()
    }
  }

  return { token, usuario, estaAutenticado, login, logout, cerrarSesionLocal }
})
