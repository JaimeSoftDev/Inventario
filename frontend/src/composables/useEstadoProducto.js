/**
 * Estados semánticos del inventario, en un único sitio para que la tarjeta,
 * la ficha y el histórico no puedan contradecirse.
 *
 * Cada estado se expresa SIEMPRE con color + icono + texto: el color solo
 * nunca basta (regla de accesibilidad del sistema de diseño).
 */

/** Umbral de "caduca pronto", en días. Máxima prioridad visual. */
export const DIAS_CADUCIDAD_URGENTE = 3

export function diasHasta(fecha) {
  if (!fecha) return null

  const objetivo = new Date(fecha)
  if (Number.isNaN(objetivo.getTime())) return null

  const hoy = new Date()
  objetivo.setHours(0, 0, 0, 0)
  hoy.setHours(0, 0, 0, 0)

  return Math.round((objetivo - hoy) / 86400000)
}

/** "2 d", "hoy", "caducado" — formato corto para los chips. */
export function formateaDias(dias) {
  if (dias === null) return null
  if (dias < 0) return 'caducado'
  if (dias === 0) return 'hoy'
  return `${dias} d`
}

/**
 * Estado de un producto a partir de su stock y su próxima caducidad.
 *
 * @returns {{clave: 'caduca'|'bajo'|'ok', tono: string, icono: string, texto: string, requiereAccion: boolean}}
 */
export function estadoDe(producto) {
  const stock = Number(producto?.stock_actual ?? 0)
  const minimo = Number(producto?.stock_minimo ?? 0)
  const dias = diasHasta(producto?.proxima_caducidad)

  if (dias !== null && dias <= DIAS_CADUCIDAD_URGENTE) {
    return {
      clave: 'caduca',
      tono: 'acento',
      icono: 'reloj',
      texto: formateaDias(dias),
      requiereAccion: true,
    }
  }

  if (minimo > 0 && stock < minimo) {
    return {
      clave: 'bajo',
      tono: 'bajo',
      icono: 'flechaBajaDiag',
      texto: `mín. ${formateaCantidad(minimo)}`,
      requiereAccion: true,
    }
  }

  return {
    clave: 'ok',
    tono: 'oliva',
    // Sin fecha no hay nada que vigilar: se dice en texto llano, sin chip,
    // para que los chips signifiquen siempre "esto tiene un plazo".
    icono: null,
    texto: dias !== null ? formateaDias(dias) : null,
    textoLlano: dias === null ? 'Sin caducidad cercana' : null,
    requiereAccion: false,
  }
}

/**
 * Concuerda la unidad con la cantidad: "1 brik" pero "6 briks". Solo afecta
 * a abreviaturas que se pluralizan con -s (uds, briks, packs); las de
 * magnitud (g, ml, L) son invariables.
 */
export function unidadPara(cantidad, unidad) {
  if (!unidad) return ''
  if (Number(cantidad) !== 1) return unidad
  return unidad.length > 2 && unidad.endsWith('s') ? unidad.slice(0, -1) : unidad
}

/** Las cantidades son decimales en base de datos pero se leen como enteros. */
export function formateaCantidad(valor) {
  const numero = Number(valor ?? 0)
  if (Number.isNaN(numero)) return '0'
  return Number.isInteger(numero) ? String(numero) : String(Number(numero.toFixed(3)))
}

/**
 * Importes en euros con formato español (1.234,50 €).
 *
 * Devuelve null cuando no hay precio, para que quien lo pinte pueda
 * omitir el dato en vez de escribir "0 €", que significa otra cosa: un
 * producto sin precio anotado no es un producto gratis.
 */
const FORMATO_EUROS = new Intl.NumberFormat('es-ES', {
  style: 'currency',
  currency: 'EUR',
})

export function formateaPrecio(valor) {
  if (valor === null || valor === undefined || valor === '') return null

  const numero = Number(valor)

  return Number.isFinite(numero) ? FORMATO_EUROS.format(numero) : null
}

/** Lo que cuesta una cantidad a un precio unitario. Null si falta alguno. */
export function importeDe(cantidad, precioUnitario) {
  if (precioUnitario === null || precioUnitario === undefined) return null

  const total = Number(cantidad) * Number(precioUnitario)

  return Number.isFinite(total) ? total : null
}
