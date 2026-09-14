<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
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
    router.replace(route.query.redirect ?? { name: 'stock' })
  } catch (e) {
    error.value = e.response?.data?.message ?? 'No se pudo iniciar sesión.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div class="mx-auto flex min-h-dvh w-full max-w-[460px] flex-col justify-center px-6 py-10">
    <span
      class="flex h-20 w-20 items-center justify-center self-center rounded-[26px] bg-acento-500 text-fondo shadow-md"
    >
      <AppIcon name="caja" :size="40" />
    </span>

    <h1 class="mt-7 text-center text-[36px] leading-none">Despensa</h1>
    <p class="mt-3 text-center text-[15px] text-arena-600">
      El inventario de casa, con quién compra y quién consume.
    </p>

    <form class="mt-9" @submit.prevent="enviar">
      <label class="block">
        <span class="etiqueta-seccion">Email</span>
        <input
          v-model="email"
          type="email"
          required
          autocomplete="username"
          class="mt-2 w-full rounded-md border border-arena-300 bg-arena-50 px-4 py-3.5 outline-none focus:border-acento-400"
        />
      </label>

      <label class="mt-4 block">
        <span class="etiqueta-seccion">Contraseña</span>
        <input
          v-model="password"
          type="password"
          required
          autocomplete="current-password"
          class="mt-2 w-full rounded-md border border-arena-300 bg-arena-50 px-4 py-3.5 outline-none focus:border-acento-400"
        />
      </label>

      <p v-if="error" class="mt-4 rounded-md bg-acento-100 px-4 py-3 text-[14px] text-acento-800">
        {{ error }}
      </p>

      <AppButton
        tamano="lg"
        bloque
        class="mt-7"
        :deshabilitado="enviando"
        @click="enviar"
      >
        {{ enviando ? 'Entrando…' : 'Entrar' }}
      </AppButton>

      <!-- Permite enviar con Enter sin duplicar el botón visible. -->
      <button type="submit" class="sr-only">Entrar</button>
    </form>
  </div>
</template>
