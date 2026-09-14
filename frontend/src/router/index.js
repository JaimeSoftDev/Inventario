import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { publica: true },
  },
  {
    path: '/',
    name: 'productos',
    component: () => import('@/views/ProductosView.vue'),
  },
  {
    path: '/productos/nuevo',
    name: 'productos.nuevo',
    component: () => import('@/views/ProductoFormView.vue'),
  },
  {
    path: '/productos/:id/stock',
    name: 'productos.stock',
    component: () => import('@/views/StockProductoView.vue'),
    props: true,
  },
  {
    path: '/compra',
    name: 'compra',
    component: () => import('@/views/CompraFormView.vue'),
  },
  {
    path: '/consumo',
    name: 'consumo',
    component: () => import('@/views/ConsumoFormView.vue'),
  },
  {
    path: '/movimientos',
    name: 'movimientos',
    component: () => import('@/views/MovimientosView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (!to.meta.publica && !auth.estaAutenticado) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.name === 'login' && auth.estaAutenticado) {
    return { name: 'productos' }
  }
  return true
})

export default router
