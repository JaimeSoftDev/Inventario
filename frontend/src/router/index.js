import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { publica: true, nav: false },
  },
  {
    path: '/',
    name: 'stock',
    component: () => import('@/views/StockView.vue'),
  },
  {
    path: '/productos/nuevo',
    name: 'producto.nuevo',
    component: () => import('@/views/ProductoNuevoView.vue'),
    meta: { nav: false },
  },
  {
    path: '/productos/:id',
    name: 'producto',
    component: () => import('@/views/ProductoView.vue'),
    props: true,
  },
  {
    path: '/historico',
    name: 'historico',
    component: () => import('@/views/HistoricoView.vue'),
  },
  {
    path: '/cola',
    name: 'cola',
    component: () => import('@/views/ColaView.vue'),
    meta: { nav: false },
  },
  {
    path: '/ajustes',
    name: 'ajustes',
    component: () => import('@/views/AjustesView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  // El token vive en localStorage: la sesión sobrevive a cerrar la app, que
  // es lo que permite abrir el inventario sin conexión.
  if (!to.meta.publica && !auth.estaAutenticado) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.name === 'login' && auth.estaAutenticado) {
    return { name: 'stock' }
  }
  return true
})

export default router
