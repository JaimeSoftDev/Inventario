<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const enviando = ref(false)
const error = ref(null)

async function enviar() {
  enviando.value = true
  error.value = null
  try {
    await auth.login(email.value, password.value)
    router.replace(route.query.redirect ?? { name: 'productos' })
  } catch (e) {
    error.value = e.response?.data?.message ?? 'No se pudo iniciar sesión.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div class="login">
    <h1>Inventario Doméstico</h1>
    <form class="card" @submit.prevent="enviar">
      <div class="form-grupo">
        <label for="email">Email</label>
        <input id="email" v-model="email" type="email" required autocomplete="username" />
      </div>
      <div class="form-grupo">
        <label for="password">Contraseña</label>
        <input
          id="password"
          v-model="password"
          type="password"
          required
          autocomplete="current-password"
        />
      </div>
      <p v-if="error" class="mensaje-error">{{ error }}</p>
      <div class="form-acciones">
        <button type="submit" :disabled="enviando">
          {{ enviando ? 'Entrando…' : 'Entrar' }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.login {
  max-width: 320px;
  margin: 3rem auto;
}
</style>
