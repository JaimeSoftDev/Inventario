<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ActualizacionDisponible from '@/components/ActualizacionDisponible.vue'
import IndicadorConexion from '@/components/IndicadorConexion.vue'

const auth = useAuthStore()
const route = useRoute()
const mostrarNav = computed(() => !route.meta.publica)
</script>

<template>
  <div class="app-shell">
    <header v-if="mostrarNav" class="app-header">
      <nav class="app-nav">
        <RouterLink to="/">Productos</RouterLink>
        <RouterLink to="/compra">Comprar</RouterLink>
        <RouterLink to="/consumo">Consumir</RouterLink>
        <RouterLink to="/movimientos">Movimientos</RouterLink>
      </nav>
      <div class="app-header__usuario">
        <IndicadorConexion />
        <span v-if="auth.usuario">{{ auth.usuario.name }}</span>
        <button type="button" @click="auth.logout()">Salir</button>
      </div>
    </header>

    <main class="app-main">
      <RouterView />
    </main>

    <ActualizacionDisponible />
  </div>
</template>

<style scoped>
.app-shell {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.app-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e2e2;
}

.app-nav {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.app-nav a {
  text-decoration: none;
  font-weight: 600;
  color: inherit;
  opacity: 0.7;
}

.app-nav a.router-link-active {
  opacity: 1;
  text-decoration: underline;
}

.app-header__usuario {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.app-main {
  flex: 1;
  padding: 1rem;
  max-width: 720px;
  margin: 0 auto;
  width: 100%;
  box-sizing: border-box;
}
</style>
