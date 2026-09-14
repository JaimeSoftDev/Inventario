# Despliegue en producción (Hostinger)

Guía concreta para `jaimesoftdev.com`. Los valores ya están puestos; si
despliegas en otro dominio o usuario, hay que cambiarlos también en
`deploy/laravel.php` y `deploy/.htaccess`.

| Dato | Valor |
|---|---|
| Usuario del hosting | `u522908681` |
| Dominio | `jaimesoftdev.com` |
| Código de la aplicación | `/home/u522908681/inventario` |
| Raíz web | `/home/u522908681/domains/jaimesoftdev.com/public_html` |
| API | `https://jaimesoftdev.com/api` |

**Requisito**: plan Premium Web o superior. El plan Single no tiene SSH y
sin SSH no se pueden ejecutar `composer` ni `artisan`.

## Por qué esta estructura

Hostinger no permite cambiar el document root en los planes compartidos, y
Laravel necesita que apunte a `public/`. Por eso la aplicación vive fuera de
`public_html` (donde el `.env` y el código no son accesibles por web) y en la
raíz web solo quedan el build de la PWA y un front controller de diez líneas.

Como la API se sirve desde el mismo dominio que la PWA, no hace falta
configurar CORS y las reglas de caché del service worker funcionan tal cual.

```
/home/u522908681/
├── inventario/                    ← git clone (backend + frontend)
└── domains/jaimesoftdev.com/public_html/
    ├── .htaccess                  ← de deploy/
    ├── laravel.php                ← de deploy/
    └── index.html, assets/, sw.js ← build del frontend
```

---

## 1. Preparar en hPanel

1. **PHP 8.3 o superior**: Sitios web → Panel → Avanzado → Configuración PHP
2. **Base de datos MySQL**: Bases de datos → MySQL. Anota nombre, usuario y contraseña
3. **Acceso SSH**: Avanzado → Acceso SSH → Activar
4. **SSL**: Seguridad → SSL. Sin HTTPS el navegador no registra el service
   worker, así que la app pierde todo el funcionamiento sin conexión
5. **Forzar HTTPS**: Seguridad → activar la redirección

## 2. Backend

```bash
ssh -p PUERTO u522908681@jaimesoftdev.com
```

```bash
cd ~
git clone https://github.com/JaimeSoftDev/Inventario.git inventario
cd ~/inventario/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env
```

Ajusta en el `.env`:

```ini
APP_NAME="Inventario"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jaimesoftdev.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u522908681_inventario
DB_USERNAME=u522908681_usuario
DB_PASSWORD=tu_password

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

`APP_DEBUG=false` no es opcional: con `true`, cualquier error muestra una
traza con rutas del servidor y fragmentos de configuración.

`SESSION_DRIVER` y `CACHE_STORE` en `file` evitan tablas innecesarias: la
API autentica con tokens, no con sesiones.

```bash
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
chmod -R 775 storage bootstrap/cache
```

## 3. Usuarios y catálogos iniciales

La API no tiene registro público, y sin unidades de medida ni ubicaciones no
se pueden dar de alta productos. Este paso crea lo mínimo:

```bash
php artisan tinker --execute="
\App\Models\User::create([
  'name' => 'Jaime',
  'email' => 'jaime@jaimesoftdev.com',
  'password' => \Illuminate\Support\Facades\Hash::make('CAMBIA_ESTA_PASSWORD'),
  'puede_atribuir_a_otros' => true,
]);
foreach (['Nevera','Despensa','Congelador','Baño'] as \$n) {
  \App\Models\Ubicacion::create(['nombre' => \$n]);
}
foreach ([['Unidad','uds'],['Kilogramo','kg'],['Litro','L'],['Paquete','packs'],['Gramo','g'],['Brik','briks']] as [\$n,\$a]) {
  \App\Models\UnidadMedida::create(['nombre' => \$n, 'abreviatura' => \$a]);
}
echo 'listo';
"
```

Para el resto de la casa, repite el `User::create`. Deja
`puede_atribuir_a_otros` fuera (o en `false`) si esa persona solo debe poder
registrar movimientos a su nombre.

## 4. Publicar la API

```bash
cd ~/domains/jaimesoftdev.com/public_html
cp ~/inventario/deploy/laravel.php .
cp ~/inventario/deploy/.htaccess .
```

Comprueba que responde:

```bash
curl -s https://jaimesoftdev.com/api/login -X POST \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"jaime@jaimesoftdev.com","password":"CAMBIA_ESTA_PASSWORD"}'
```

Debe devolver un JSON con `token`. Ver más abajo si no es así.

## 5. Frontend

Este paso va **en tu ordenador**: Hostinger no permite ejecutar `npm` por
SSH. El resultado del build es estático, así que se compila en local y se
sube ya hecho.

```bash
git clone https://github.com/JaimeSoftDev/Inventario.git
cd Inventario/frontend
echo "VITE_API_URL=https://jaimesoftdev.com/api" > .env
npm ci
npm run build
```

Sube **el contenido** de `frontend/dist/` (no la carpeta) a `public_html/`,
junto a `laravel.php` y `.htaccess`, por SFTP o con el Administrador de
archivos de hPanel.

`VITE_API_URL` se incrusta en el build: si cambia el dominio, hay que
recompilar y volver a subir.

## 6. Comprobar

1. `https://jaimesoftdev.com` muestra la pantalla de acceso
2. Entras con tu usuario y ves el inventario vacío
3. Creas un producto y le registras una compra
4. En el móvil: menú del navegador → "Añadir a pantalla de inicio"
5. Modo avión → abre la app, registra un consumo (queda "En cola") → desactiva
   el modo avión y se sincroniza solo

---

## Actualizar a una versión nueva

```bash
# Backend, por SSH
cd ~/inventario && git pull
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
```

```bash
# Frontend, en local
cd frontend && npm ci && npm run build
# y subir de nuevo el contenido de dist/
```

Si cambiaste `deploy/laravel.php` o `deploy/.htaccess`, vuelve a copiarlos a
`public_html` después del `git pull`.

---

## Si algo falla

**La API devuelve HTML en vez de JSON**
El `.htaccess` no se está aplicando. Comprueba que está en `public_html` (no
en un subdirectorio) y que se subió con el punto inicial: los clientes FTP
a veces ocultan los ficheros que empiezan por punto.

**Todo responde 401 aunque la contraseña sea correcta**
El servidor está descartando la cabecera `Authorization`. Es justo lo que
evita la primera regla del `.htaccess`; verifica que esa parte llegó entera.

**Error 500 al entrar**
Mira `~/inventario/backend/storage/logs/laravel.log`. Lo más habitual es que
falten permisos (`chmod -R 775 storage bootstrap/cache`) o que la ruta de
`laravel.php` no coincida con dónde está el repositorio.

**La app carga pero las rutas internas dan 404 al recargar**
Falta el respaldo a `index.html` del `.htaccess`, o `index.html` no está en
la raíz de `public_html`.

**Los cambios nuevos no aparecen tras actualizar**
El service worker sirve la versión anterior. Debería resolverse solo con el
aviso de "nueva versión disponible"; si no, comprueba que `sw.js` se sirve
con `Cache-Control: no-cache`:

```bash
curl -sI https://jaimesoftdev.com/sw.js | grep -i cache-control
```
