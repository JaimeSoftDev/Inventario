# Inventario Doméstico

Sistema de inventario doméstico (tipo Grocy) para gestionar el stock de
productos, registrar compras y consumos, y **atribuir cada movimiento a
cualquier usuario del hogar**, independientemente de quién esté logueado
realizando la acción. Funciona como PWA instalable en móvil, con soporte
offline para registrar consumos sin conexión.

## Estructura del repositorio

```
backend/   API REST en Laravel (Sanctum, sin Blade)
frontend/  PWA en Vue 3 + Vite (Pinia, Dexie.js, vite-plugin-pwa)
```

## Decisión clave de diseño

`movimientos_stock` separa **quién ejecutó la acción**
(`usuario_registrador_id`, viene del token activo) de **a quién se atribuye**
(`usuario_atribuido_id`, elegible libremente en el formulario). Esto permite
registrar un consumo o compra a nombre de cualquier miembro del hogar sin
perder trazabilidad real de auditoría.

- Un usuario siempre puede atribuirse movimientos a sí mismo.
- Atribuir a otro usuario requiere el permiso `puedeAtribuirAOtros()`
  (columna `users.puede_atribuir_a_otros`).
- El consumo aplica **FEFO** (first-expired-first-out) con desempate
  "abierto primero", repartiéndose entre varias entradas de stock si hace
  falta, todo dentro de una única transacción de BD.

## Backend (Laravel)

### Requisitos

- PHP 8.3+
- Composer

### Puesta en marcha

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # usa SQLite por defecto
php artisan migrate --seed
php artisan serve
```

Esto deja la API en `http://localhost:8000/api` y crea dos usuarios de
prueba (contraseña `password`):

- `ana@example.com` — puede atribuir movimientos a otros usuarios.
- `luis@example.com` — solo puede atribuirse movimientos a sí mismo.

### Datos de demostración

Para ver la app poblada (cuatro miembros del hogar, productos con
caducidad inminente, stock bajo, varios lotes y movimientos atribuidos a
terceros):

```bash
php artisan migrate:fresh --seed --seeder="Database\Seeders\DemoSeeder"
```

### Tests

```bash
cd backend
php artisan test
```

La suite de feature tests cubre el checklist funcional: alta de producto,
compra con caducidad, stock agregado, consumo simple y repartido entre
varias entradas (verificando el orden FEFO), atribución con y sin permiso
(403), consumo mayor al disponible (422), y que dos consumos concurrentes
sobre el mismo producto no descuadran el stock.

### Arquitectura

- **Modelos Eloquent** con las relaciones del dominio (`app/Models`).
- **Repository pattern + Criteria** (`app/Repositories`): los repositorios
  no contienen lógica de query embebida; `EntradasDisponiblesFEFOCriteria`
  encapsula el orden FEFO como una pieza de query reutilizable.
- **Service layer** (`app/Services/MovimientoStockService.php`): valida
  permisos de atribución, aplica FEFO y envuelve el descuento de stock en
  `DB::transaction()` con `lockForUpdate()` para evitar condiciones de
  carrera entre usuarios consumiendo el mismo producto a la vez.
- **Form Requests, Controllers delgados y API Resources**
  (`app/Http/{Requests,Controllers,Resources}`): los controllers delegan al
  Service y traducen las excepciones de negocio
  (`StockInsuficienteException` → 422, `AtribucionNoAutorizadaException` →
  403).
- Autenticación con **Sanctum** (bearer tokens, sin cookies de sesión):
  `POST /api/login` devuelve un token que el frontend guarda y envía como
  `Authorization: Bearer …`.

## Frontend (Vue 3 + Vite, PWA)

### Requisitos

- Node.js 20+

### Puesta en marcha

```bash
cd frontend
npm install
cp .env.example .env   # ajusta VITE_API_URL si el backend no está en localhost:8000
npm run dev
```

### Build de producción (genera el service worker)

```bash
npm run build
npm run preview
```

### Sistema de diseño

La interfaz sigue un sistema propio, definido como tokens de Tailwind v4 en
`src/style.css` (`@theme`) y materializado en componentes reutilizables:

- **Paleta**: fondo `#f5ead8`, superficie `#ebddc5`, tinta `#201e1d`,
  acento terracota `#c67139` (acción y urgencia) y oliva `#7a8a5e` (estado
  saludable), cada uno con su rampa 100–900.
- **Tipografía**: Caprasimo para títulos y cifras, Figtree para el resto
  (400 texto, 700 nombre de producto, 800 etiquetas de sección). Van
  empaquetadas con la app, no desde un CDN, para que la identidad se
  mantenga sin conexión.
- **Regla de accesibilidad**: un estado nunca se comunica solo con color;
  siempre franja + icono + texto. Los objetivos táctiles miden 44px.
- **Componentes** (`src/components`): `ProductCard`, `MemberChip`,
  `StatusStripe`, `EstadoChip`, `QtyStepper`, `OfflineBanner`,
  `MovementRow`, `BottomSheet`, `FiltroChips`, `SelectorMiembro`.

Lenguaje visual del offline, repetido en toda la app: **discontinuo** =
guardado en local y aún sin confirmar; **terracota sólido** = conflicto que
necesita una decisión humana.

### Pantallas

- **Stock**: lo urgente primero (caduca en ≤3 días o por debajo del
  mínimo), luego agrupado por ubicación. Cada fila lleva su stepper, y se
  arrastra en las dos direcciones: a la **izquierda** consume 1 a nombre
  del usuario actual (con aviso y opción de deshacer), y arrastrando más
  se abre la hoja para elegir cantidad y persona; a la **derecha** consume
  1 a nombre de otro miembro, convirtiendo la fila en una tira de avatares
  para resolverlo con un solo toque más. El gesto derecho no aparece para
  quien no tiene `puede_atribuir_a_otros`, porque acabaría en un 403.
- **Ficha de producto**: stock total, próxima caducidad y desglose por
  lotes en el mismo orden en que los consumirá FEFO.
- **Hojas de consumo y de alta de stock**: selector "a nombre de",
  cantidad con atajos, caducidad con atajos y ubicación. La cifra de
  cantidad se ajusta con los botones ±, pero también se teclea: al
  enfocarla se selecciona entera, admite coma decimal y avisa por texto
  cuando lo escrito supera el stock disponible.
- **Histórico**: agrupado por día, distinguiendo a quién se atribuye
  (avatar sólido) de quién lo registró (avatar discontinuo, solo si
  difiere).
- **Cola de sincronización**: separa lo que solo espera red de lo que
  necesita revisión, con las acciones para resolverlo.

### Arquitectura

- **Pinia** para el estado global (`src/stores`): sesión, productos,
  usuarios y catálogos auxiliares.
- **Dexie.js** (`src/db/dexie.js`) para IndexedDB: cachés de solo lectura
  (`productos_cache`, `usuarios_cache`, `entradas_stock_cache`) y una cola
  de escritura offline (`cola_movimientos`).
- **`useMovimientoStock`** (`src/composables`): si `navigator.onLine` es
  `false`, o la petición falla por un error de red (no por un 422/403), se
  encola en Dexie en lugar de fallar. Los errores de negocio se propagan
  para que el formulario los muestre.
- **`useSincronizador`**: escucha el evento `online` y reintenta la cola en
  orden; un fallo de negocio (422/403) marca el ítem como `error` y no se
  reintenta automáticamente (el contexto pudo cambiar mientras se estaba
  offline); un fallo de red sí se reintenta en la siguiente sincronización.
- **PWA** (`vite-plugin-pwa`, `registerType: 'prompt'`): manifest con
  iconos 192/512 normales y maskable; `/api/productos` en
  StaleWhileRevalidate, `/api/usuarios` en CacheFirst, y los `POST` de
  `/api/movimientos/*` en NetworkOnly (la cola offline la gestiona Dexie,
  no Workbox).

## Fuera de alcance

Escaneo de código de barras: descartado explícitamente, no hay tabla
`codigos_barras` ni dependencias relacionadas.
