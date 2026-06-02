<p align="center">
  <img src="https://rapanel.playgame.cl/images/logo.png" alt="rApanel" width="280" />
</p>

<p align="center">
  <a href="README.md">English</a> · <a href="README-ES.md">Español</a> · <a href="README-PT.md">Português</a> · <a href="README-FR.md">Français</a>
</p>

---

# rApanel

Panel de control web para servidores **rAthena** de Ragnarok Online. Los jugadores registran una cuenta maestra en el panel y gestionan sus cuentas de juego, personajes y almacenamiento desde un dashboard. Los administradores disponen de un back-office completo para usuarios, cuentas, noticias, descargas, Item DB, Mob DB y MVP Cards.

**Stack:** Laravel 12 · Inertia.js · Vue 3 · Tailwind CSS · MySQL / MariaDB

---

## Requisitos

| Requisito | Mínimo |
|---|---|
| PHP | 8.2+ |
| Node.js | 20+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| MariaDB | 10.6+ (alternativa a MySQL) |
| rAthena | Cualquier build reciente |

> El panel debe tener acceso de red a la base de datos de rAthena y a los puertos login/char/map si deseas que el indicador de estado del servidor funcione en tiempo real.

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/KhrizPlayCL/rapanel.git
cd rapanel
```

### 2. Instalar dependencias

```bash
composer install
npm install
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Abre `.env` y completa las secciones descritas más abajo.

### 4. Ejecutar las migraciones

Las migraciones solo tocan la **base de datos del panel** (tablas `ra_*`). Nunca modifican la base de datos de rAthena.

```bash
php artisan migrate
```

### 5. Compilar los assets del frontend

```bash
# Producción
npm run build

# Desarrollo (HMR)
npm run dev
```

### 6. Crear el primer administrador

Regístrate normalmente en el panel y luego promueve el usuario directamente en la base de datos:

```sql
UPDATE ra_users SET role = 'admin' WHERE email = 'tu@email.com';
```

---

## Configuración del entorno

### Base de datos del panel (requerida)

El panel crea sus propias tablas con prefijo `ra_` en esta base de datos.

```env
DB_CONNECTION=mysql       # mysql o mariadb — todas las conexiones usan este driver automáticamente
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rapanel
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### Base de datos principal de rAthena (requerida)

Apunta a la base de datos de rAthena (`login`, `char`, `inventory`, etc.).

```env
DB_MAIN_DATABASE=ragnarok
DB_MAIN_USERNAME=tu_usuario
DB_MAIN_PASSWORD=tu_contraseña
# DB_MAIN_HOST y DB_MAIN_PORT heredan los valores anteriores por defecto
```

### Bases de datos log y web de rAthena (opcionales)

```env
DB_LOG_DATABASE=ragnarok_log
DB_WEB_DATABASE=ragnarok_web
```

### Configuración del servidor rAthena

```env
RA_SERVER_NAME="Mi Servidor RO"

# Emulador y modo de juego
RA_EMULATOR=rathena           # solo rathena está soportado por ahora
RA_GAME_MODE=renewal          # renewal (estable) | pre-renewal (en desarrollo)

# Reglas de cuenta
RA_MAX_GAME_ACCOUNTS=3        # máximo de cuentas de juego por cuenta maestra
RA_ACCOUNT_NOCASE=true        # userids sin distinción de mayúsculas (defecto rAthena)
RA_USE_MD5_PASSWORDS=true     # contraseñas con hash MD5 (defecto rAthena)
RA_REQUIRE_EMAIL_VERIFY=false # activar verificación de email

# IPs y puertos del servidor (para el chequeo de estado en vivo)
RA_LOGIN_IP=127.0.0.1
RA_LOGIN_PORT=6900
RA_CHAR_IP=127.0.0.1
RA_CHAR_PORT=6121
RA_MAP_IP=127.0.0.1
RA_MAP_PORT=5121

# Mapa de destino al resetear posición de personaje
RA_RESET_MAP=prontera
RA_RESET_X=150
RA_RESET_Y=180

# Funcionalidades opcionales
RA_VIP_ENABLED=false
RA_BANK_ENABLED=false
RA_CASHPOINTS_ENABLED=false

# Ruta al log del servidor rAthena (consola en el admin)
RA_LOG_PATH=

# Consola en vivo del admin — ws-server (requerido para la pestaña Live Console)
RA_WS_PORT=3001                 # puerto donde escucha el ws-server (por defecto 3001)
RA_WS_SECRET=                   # secreto compartido entre rapanel y el ws-server
# Sobrescribir las URLs auto-detectadas solo cuando sea necesario:
# RA_WS_INTERNAL_URL=http://127.0.0.1:3001   # Laravel → ws-server (server-side, siempre http://)
# RA_WS_PUBLIC_URL=ws://ip:3001              # browser → ws-server (ws:// para HTTP, wss://dominio/ws-proxy para HTTPS)
```

---

## Arquitectura de base de datos

El panel usa un diseño de **doble base de datos**:

| Conexión | Prefijo | Propósito |
|---|---|---|
| `mysql` | `ra_` | Tablas del panel (`ra_users`, `ra_sessions`, `ra_news`, …) |
| `mysql_main` | — | Tablas de rAthena (`login`, `char`, `inventory`, …) |
| `mysql_log` | — | Tablas de log de rAthena |
| `mysql_web` | — | Tablas web de rAthena |

El código del panel usa siempre `DB::table('users')` (sin prefijo en el código — Laravel aplica `ra_` automáticamente). Las consultas a tablas de rAthena usan `DB::connection('mysql_main')`.

---

## Opcional: Assets del juego

El panel puede mostrar iconos, ilustraciones, imágenes de cartas y más si los colocas en `public/data/`:

```
public/data/
├── items/
│   ├── item/           # Iconos de ítems        → /data/items/item/{nameid}.png
│   ├── collection/     # Ilustraciones de ítems → /data/items/collection/{nameid}.png
│   └── cards/          # Arte de cartas         → /data/items/cards/{nameid}.png
├── icon_jobs/          # Iconos de clase/trabajo → /data/icon_jobs/icon_jobs_{class}.png
├── maps/               # Vistas previas de mapas → /data/maps/{map}.png
├── monsters/           # Sprites de monstruos    → /data/monsters/{id}.gif
└── gameaccount/        # Iconos de funciones (banco, VIP, cash points, etc.)
```

Estas carpetas están en `.gitignore` — los archivos se gestionan de forma independiente.

---

## Opcional: Importar Item DB y Mob DB

El panel incluye importadores para las bases de datos YAML y LUA de rAthena. Ve a **Admin → Item DB** o **Admin → Mob DB** y sube el archivo correspondiente de tu instalación de rAthena:

| Sección admin | Archivos a subir | Ubicación en rAthena |
|---|---|---|
| Item DB (YML) | Todos los archivos `item_db_*.yml` de la carpeta de tu modo de juego — seleccionarlos todos a la vez. Pre-renewal tiene 3 archivos; renewal puede tener más. | `db/re/` o `db/pre-re/` |
| Item DB (LUA) | `itemInfo.lua` — opcional, enriquece los nombres de display | cliente `data/luafiles514/lua files/datainfo/` |
| Mob DB | `mob_db.yml` | `db/re/` o `db/pre-re/` |
| Map DB — Caché | `map_cache.dat` — importa las dimensiones de los mapas | `db/map_cache.dat` |
| Map DB — Spawns | Uno o más archivos de spawn | `db/re/` o `db/pre-re/` |
| Map DB — Info | `mapInfo.lua` — opcional, enriquece los nombres de display de los mapas | cliente `data/luafiles514/lua files/datainfo/` |

Una vez importados ambos, **Admin → MvP Cards** auto-descubre las cartas de monstruos MVP, Boss y Normal, y permite activar cuáles son visibles en la página pública.

---

## Funcionalidades

### Dashboard del jugador
- Registrar una cuenta maestra y vincularla a cuentas rAthena existentes mediante un token de reclamación
- Crear nuevas cuentas de juego rAthena (hasta el límite configurado)
- Cambiar contraseña y género de la cuenta de juego
- Autenticación de dos factores (TOTP / app autenticadora)
- Ver personajes con clase, stats, equipamiento, inventario, carrito y almacenamiento
- Resetear posición y apariencia del personaje
- Interfaz responsive para móvil — instalable como PWA en Android e iOS

### Páginas públicas
- **Noticias** — con reacciones y comentarios
- **Descargas** — archivos categorizados
- **Who Sell** — búsqueda en el mercado de vendings
- **Item DB** — base de datos de ítems con modal de detalle completo
- **MVP Cards** — cartas activas en el servidor con cantidades y propiedades
- **Wiki** — base de conocimiento estilo docs, organizada en secciones y artículos

### Panel de administración
- Gestionar cuentas maestras y de juego (baneo, grupo, VIP)
- Moderar noticias, comentarios y descargas
- Importar Item DB y Mob DB desde archivos YAML/LUA de rAthena
- Configurar visibilidad de MVP Cards por carta
- Gestionar secciones y artículos de la Wiki (CRUD)
- Ver logs de acciones y consola del servidor en vivo

---

## Consideraciones

- **Solo rAthena** — Hercules no está soportado por el momento. El soporte está planificado para una versión futura.
- **Modo de juego** — Renewal es el modo principal soportado. Pre-renewal es funcional pero la lógica sigue refinándose.
- **No es necesario el mismo host** — la DB del panel y la de rAthena pueden estar en servidores distintos siempre que las credenciales sean accesibles.
- **Nunca ejecutes `migrate:fresh` en producción** — las migraciones solo crean tablas del panel, pero es una buena práctica igualmente.
- **Contraseñas MD5** — `RA_USE_MD5_PASSWORDS=true` coincide con el default de rAthena. Si tu servidor usa contraseñas en texto plano, ponlo en `false`.
- **Verificación de email** — desactivada por defecto. Actívala y configura un driver de correo en `.env` si deseas cuentas verificadas.
- **Prefijo DB** — todas las tablas del panel usan el prefijo `ra_`. No escribas `ra_` en el código; Laravel lo aplica automáticamente. Escribir `DB::table('ra_users')` produciría `ra_ra_users`.

---

## Comandos de desarrollo

```bash
npm run dev          # Servidor Vite con HMR
npm run build        # Build de producción

php artisan serve                   # Servidor PHP de desarrollo
php artisan migrate                 # Ejecutar migraciones pendientes
php artisan cache:clear             # Limpiar caché de la aplicación
php artisan config:clear            # Limpiar caché de config (tras cambios en .env)
php artisan test                    # Ejecutar suite de tests
```

---

## Licencia

MIT
