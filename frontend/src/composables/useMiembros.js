/**
 * Identidad visual de los miembros del hogar: cada persona se reconoce por
 * inicial + color estable, nunca solo por el color (regla de accesibilidad
 * del sistema de diseño). El color se deriva del id para que sea el mismo
 * en todos los dispositivos sin guardarlo en base de datos.
 */
const PALETA = [
  { fondo: 'var(--color-oliva-500)', texto: '#f5ead8' },
  { fondo: 'var(--color-acento-500)', texto: '#f5ead8' },
  { fondo: 'var(--color-arena-700)', texto: '#f5ead8' },
  { fondo: 'var(--color-oliva-400)', texto: '#272d1f' },
  { fondo: 'var(--color-acento-700)', texto: '#f5ead8' },
  { fondo: 'var(--color-arena-500)', texto: '#f5ead8' },
]

export function inicialesDe(nombre = '') {
  const limpio = String(nombre).trim()
  if (!limpio) return '··'

  const palabras = limpio.split(/\s+/)
  if (palabras.length === 1) {
    return palabras[0].slice(0, 2).toUpperCase()
  }
  return (palabras[0][0] + palabras[1][0]).toUpperCase()
}

export function colorDe(id) {
  const indice = Math.abs(Number(id) || 0) % PALETA.length
  return PALETA[indice]
}

export function useMiembros() {
  return { inicialesDe, colorDe }
}
