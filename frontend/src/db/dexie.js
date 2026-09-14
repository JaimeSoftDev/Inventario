import Dexie from 'dexie'

/**
 * Base de datos local (IndexedDB) usada para:
 *  - Cachear catálogos (productos, usuarios, entradas de stock) y poder
 *    mostrarlos sin conexión.
 *  - Encolar movimientos de stock (compra/consumo/corrección) cuando no hay
 *    red, para sincronizarlos automáticamente al recuperar la conexión.
 */
export const db = new Dexie('inventario-domestico')

db.version(1).stores({
  // Cachés de solo lectura: se sobrescriben con cada respuesta de la API.
  productos_cache: 'id, nombre, categoria_id',
  usuarios_cache: 'id, name',
  entradas_stock_cache: 'id, producto_id, fecha_caducidad',

  // Cola de escritura offline. `id` autoincremental local (nunca viaja al
  // backend), `estado` es 'pendiente' | 'sincronizando' | 'error'.
  cola_movimientos: '++id, estado, tipo, creado_en',
})

export default db
