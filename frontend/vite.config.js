import { fileURLToPath, URL } from 'node:url'

import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    VitePWA({
      // No se activa la nueva versión sola a media acción del usuario: se
      // le avisa (ver ActualizacionDisponible.vue) y decide cuándo recargar.
      registerType: 'prompt',
      // El registro del service worker lo dispara a mano
      // ActualizacionDisponible.vue vía virtual:pwa-register/vue.
      injectRegister: false,
      includeAssets: ['pwa-192.png', 'pwa-512.png', 'maskable-192.png', 'maskable-512.png'],
      manifest: {
        name: 'Inventario Doméstico',
        short_name: 'Inventario',
        description: 'Gestiona el stock, las compras y los consumos del hogar, incluso sin conexión.',
        theme_color: '#f5ead8',
        background_color: '#f5ead8',
        display: 'standalone',
        start_url: '/',
        icons: [
          { src: '/pwa-192.png', sizes: '192x192', type: 'image/png' },
          { src: '/pwa-512.png', sizes: '512x512', type: 'image/png' },
          { src: '/maskable-192.png', sizes: '192x192', type: 'image/png', purpose: 'maskable' },
          { src: '/maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        // Precachea el app shell (JS/CSS/HTML) y las fuentes empaquetadas:
        // van en el bundle, no en un CDN, para que la tipografía siga
        // siendo la correcta sin conexión.
        globPatterns: ['**/*.{js,css,html,png,svg,ico,woff2}'],
        runtimeCaching: [
          {
            // Catálogo de productos: se muestra lo cacheado al instante y
            // se revalida en segundo plano.
            urlPattern: ({ url }) => url.pathname === '/api/productos',
            handler: 'StaleWhileRevalidate',
            options: { cacheName: 'api-productos' },
          },
          {
            // Usuarios del hogar. Cambia poco, pero cuando cambia hay que
            // verlo: es la lista de personas a las que se atribuye un
            // consumo, y alguien recién dado de alta no puede tardar un
            // día en aparecer. Con CacheFirst pasaba justo eso.
            // Igual que /api/productos: instantáneo desde caché y
            // refrescado por detrás. Sin red, el fallback es Dexie
            // (stores/usuarios.js), no Workbox.
            urlPattern: ({ url }) => url.pathname === '/api/usuarios',
            handler: 'StaleWhileRevalidate',
            options: { cacheName: 'api-usuarios' },
          },
          {
            // Los POST de movimientos (compra/consumo/corrección) nunca se
            // sirven de caché: si no hay red, la petición falla y la cola
            // offline de Dexie se encarga (ver
            // composables/useMovimientoStock.js), no Workbox.
            urlPattern: ({ url }) => url.pathname.startsWith('/api/movimientos'),
            method: 'POST',
            handler: 'NetworkOnly',
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
