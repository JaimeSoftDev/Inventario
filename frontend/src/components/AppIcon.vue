<script setup>
import { computed } from 'vue'

/**
 * Iconografía propia en SVG inline: son pocos glifos y así viajan en el
 * bundle (sin fuente de iconos ni peticiones extra), lo que importa en una
 * PWA que debe arrancar sin conexión. Trazo de 1.75 para que acompañe al
 * peso de Figtree sin competir con él.
 */
const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 20 },
})

const GLIFOS = {
  caja: 'M12 3 3.5 7.5v9L12 21l8.5-4.5v-9L12 3Zm0 0v18M3.5 7.5 12 12l8.5-4.5',
  lista: 'M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01',
  ajustes: 'M4 7h7m4 0h5M4 17h3m4 0h9M13 4.5v5M9 14.5v5',
  buscar: 'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14ZM20 20l-4-4',
  mas: 'M12 5v14M5 12h14',
  menos: 'M5 12h14',
  flechaAbajo: 'M12 4.5v15M6 13.5l6 6 6-6',
  flechaArriba: 'M12 19.5v-15M6 10.5l6-6 6 6',
  flechaBajaDiag: 'M6 7.5 15 16.5M15 10.5v6h-6',
  atras: 'M15 5l-7 7 7 7',
  cerrar: 'M6 6l12 12M18 6 6 18',
  reloj: 'M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm0 3.75V12l3 2',
  sincronizar: 'M4.5 12a7.5 7.5 0 0 1 12.8-5.3L20 9.5M19.5 12a7.5 7.5 0 0 1-12.8 5.3L4 14.5M20 5v4.5h-4.5M4 19v-4.5h4.5',
  deshacer: 'M4 9h10.5a4.5 4.5 0 1 1 0 9H8M4 9l4-4M4 9l4 4',
  aviso: 'M12 4.5 3 19.5h18L12 4.5Zm0 5.5v4.5m0 3h.01',
  check: 'M4.5 12.5 9.5 17.5 19.5 6.5',
  carrito: 'M3 5h2.2l2.3 10.5h9.8L19.5 8H6M9.5 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm7 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z',
  lapiz: 'M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4ZM14.5 5.5l4 4',
  calendario: 'M4.5 7.5h15v12h-15v-12Zm0 4h15M8.5 4.5v4m7-4v4',
  sinConexion: 'M3 4l18 16M5 10.5a13 13 0 0 1 3.5-2.2M2 7a17.5 17.5 0 0 1 5-2.9m4-.6a17.5 17.5 0 0 1 11 3.5M19 10.5a13 13 0 0 0-3-2M8.5 14.2a8 8 0 0 1 7 .6M12 19h.01',
  personas: 'M9 11a3.2 3.2 0 1 0 0-6.4A3.2 3.2 0 0 0 9 11Zm-6 8.5c0-3 2.7-5 6-5s6 2 6 5M16 5.2a3.2 3.2 0 0 1 0 6.2m1.5 3.3c2.1.6 3.5 2.2 3.5 4.8',
  salir: 'M14 4.5h4.5v15H14M11 15.5 14.5 12 11 8.5M14.5 12H4',
  ubicacion: 'M12 21s7-5.7 7-11a7 7 0 1 0-14 0c0 5.3 7 11 7 11Zm0-8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z',
}

const d = computed(() => GLIFOS[props.name] ?? '')
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
  >
    <path :d="d" />
  </svg>
</template>
