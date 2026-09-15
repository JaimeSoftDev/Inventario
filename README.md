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

Esto deja la API en `http://localhost:8000/api` con un hogar de ejemplo de
tres miembros. **La contraseña de cada uno es su propio nombre**:

| Usuario | Correo | Contraseña | Puede atribuir a otros |
|---|---|---|---|
| Jaime | `jaimesoftdev@gmail.com` | `Jaime` | sí |
| Antonio | `antonio@example.com` | `Antonio` | no |
| Samuel | `samuel@example.com` | `Samuel` | no |

Para entrar vale cualquiera de las dos columnas: el nombre o el correo.

Que dos de los tres no puedan atribuir no es un descuido: es lo que
permite comprobar el 403 del servicio y que el gesto de "a nombre de otro"
no se ofrezca a quien no tiene el permiso.

Los datos cubren todos los estados de la interfaz: caducidad inminente,
stock bajo, un producto repartido en varios lotes, una corrección de
recuento y consumos atribuidos a terceros. El catálogo de ubicaciones y
unidades es el mismo que crea la fase 3 de [DEPLOY.md](DEPLOY.md), para
que el hogar de desarrollo se parezca al de verdad.

Es solo para desarrollo: `migrate:fresh` tira todas las tablas y estas
contraseñas son de juguete.

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
  `Authorization: Bearer …`. El campo `identificador` admite **el nombre
  del miembro o su correo**, sin distinguir mayúsculas: en el móvil
  teclear "Jaime" es mejor que teclear un correo, y el nombre ya es la
  identidad del miembro en toda la interfaz. Por eso `users.name` es
  único. Se sigue aceptando el campo `email` que envían las PWA ya
  instaladas.

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
  lotes en el mismo orden en que los consumirá FEFO. Cada lote enseña lo
  que costó esa compra, y la cabecera el valor de lo que queda.

### Precios

El producto lleva un **precio de referencia** (lo que suele costar una
unidad) y cada lote guarda el **precio real de su compra**: dos briks del
mismo producto pueden haber costado 0,89 € y 0,99 €, y el valor de la
despensa sale de sumar cada lote por lo suyo, no de estimar.

La hoja de alta de stock propone el precio de referencia y calcula el
total mientras escribes, para cuadrarlo con el ticket. El histórico
muestra el importe solo en las compras: en un consumo el precio del lote
no es un gasto de hoy, y enseñarlo ahí invitaría a sumarlo dos veces.

Un producto sin precio anotado **no es un producto gratis**, así que el
campo viaja como `null` y la interfaz lo omite en lugar de pintar 0 €. La
columna es `decimal(12,2)`, que no llega a un precio por gramo (28 €/kg
serían 0,028 €/g): para productos al peso conviene dejarlo vacío.
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
  StaleWhileRevalidate, `/api/usuarios` también (cambia poco, pero cuando
  entra alguien nuevo en el hogar hay que verlo ya, no al día siguiente), y
  los `POST` de `/api/movimientos/*` en NetworkOnly (la cola offline la
  gestiona Dexie, no Workbox). La app comprueba si hay versión nueva cada
  hora, al volver a primer plano y al recuperar la conexión: una PWA
  instalada se reanuda en lugar de recargarse, y el navegador solo mira al
  cargar de cero.

## Fuera de alcance

Escaneo de código de barras: descartado explícitamente, no hay tabla
`codigos_barras` ni dependencias relacionadas.
