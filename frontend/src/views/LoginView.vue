<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import AppIcon from '@/components/AppIcon.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const identificador = ref('')
const password = ref('')
const enviando = ref(false)
const error = ref(null)

async function enviar() {
  enviando.value = true
  error.value = null

  try {
    await auth.login(identificador.value, password.value)
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
        <span class="etiqueta-seccion">Usuario o correo</span>
        <!-- `text` y no `email`: el navegador rechazaría "Jaime" por no
             llevar arroba. La capitalización se desactiva porque el nombre
             se compara sin distinguir mayúsculas y un "Jaime" automático
             despista sobre lo que hace falta escribir. -->
        <input
          v-model="identificador"
          type="text"
          required
          autocomplete="username"
          autocapitalize="none"
          autocorrect="off"
          spellcheck="false"
          placeholder="Jaime"
          class="mt-2 w-full rounded-md border border-arena-300 bg-arena-50 px-4 py-3.5 outline-none placeholder:text-arena-400 focus:border-acento-400"
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
