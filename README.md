<p align="center">
  <img src="https://rapanel.playgame.cl/images/logo.png" alt="rApanel" width="280" />
</p>

<p align="center">
  <a href="README.md">English</a> · <a href="README-ES.md">Español</a> · <a href="README-PT.md">Português</a> · <a href="README-FR.md">Français</a>
</p>

---

# rApanel

A web-based control panel for **rAthena** Ragnarok Online emulator servers. Players register a master account on the panel and manage their in-game accounts, characters, and storage from a dashboard. Admins get a full back-office for users, game accounts, news, downloads, Item DB, Mob DB, and MVP Cards.

**Stack:** Laravel 12 · Inertia.js · Vue 3 · Tailwind CSS · MySQL / MariaDB

---

## Requirements

| Requirement | Minimum |
|---|---|
| PHP | 8.2+ |
| Node.js | 20+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| MariaDB | 10.6+ (alternative to MySQL) |
| rAthena | Any recent build |

> The panel must have network access to both the rAthena database and the login/char/map server ports if you want the live server-status check to work.

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/KhrizPlayCL/rapanel.git
cd rapanel
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and fill in the sections below.

### 4. Run migrations

Migrations only touch the **panel database** (`ra_*` tables). They never modify the rAthena database.

```bash
php artisan migrate
```

### 5. Build frontend assets

```bash
# Production
npm run build

# Development (HMR)
npm run dev
```

### 6. Create the first admin

Register normally through the panel, then promote the user directly in the database:

```sql
UPDATE ra_users SET role = 'admin' WHERE email = 'your@email.com';
```

---

## Environment Configuration

### Panel database (required)

The panel creates its own tables with an `ra_` prefix in this database.

```env
DB_CONNECTION=mysql       # mysql or mariadb — all connections use this driver automatically
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rapanel       # panel-only DB (can be the same host as rAthena)
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### rAthena main database (required)

Points to the rAthena database (`login`, `char`, `inventory`, etc.).

```env
DB_MAIN_DATABASE=ragnarok   # rAthena DB name
DB_MAIN_USERNAME=your_user
DB_MAIN_PASSWORD=your_password
# DB_MAIN_HOST and DB_MAIN_PORT default to the same values as above
```

### rAthena log and web databases (optional)

```env
DB_LOG_DATABASE=ragnarok_log
DB_WEB_DATABASE=ragnarok_web
```

### rAthena server settings

```env
RA_SERVER_NAME="My RO Server"

# Emulator and game mode
RA_EMULATOR=rathena           # only rathena is supported at this time
RA_GAME_MODE=renewal          # renewal (stable) | pre-renewal (in progress)

# Account rules
RA_MAX_GAME_ACCOUNTS=3        # max game accounts per master account
RA_ACCOUNT_NOCASE=true        # case-insensitive userids (rAthena default)
RA_USE_MD5_PASSWORDS=true     # MD5-hashed passwords (rAthena default)
RA_REQUIRE_EMAIL_VERIFY=false # toggle email verification

# Server IPs and ports (used for live status check)
RA_LOGIN_IP=127.0.0.1
RA_LOGIN_PORT=6900
RA_CHAR_IP=127.0.0.1
RA_CHAR_PORT=6121
RA_MAP_IP=127.0.0.1
RA_MAP_PORT=5121

# Character position reset target
RA_RESET_MAP=prontera
RA_RESET_X=150
RA_RESET_Y=180

# Optional features
RA_VIP_ENABLED=false
RA_BANK_ENABLED=false
RA_CASHPOINTS_ENABLED=false

# Path to rAthena server log file (admin console page)
RA_LOG_PATH=
```

---

## Database Architecture

The panel uses a **dual-database** design:

| Connection | Prefix | Purpose |
|---|---|---|
| `mysql` | `ra_` | Panel tables (`ra_users`, `ra_sessions`, `ra_news`, …) |
| `mysql_main` | — | rAthena tables (`login`, `char`, `inventory`, …) |
| `mysql_log` | — | rAthena log tables |
| `mysql_web` | — | rAthena web tables |

Panel code always uses `DB::table('users')` (no prefix in the code — Laravel applies `ra_` automatically). Raw queries to rAthena tables use `DB::connection('mysql_main')`.

---

## Optional: Game Asset Setup

The panel can display item icons, illustrations, card images, and more if you place them in `public/data/`:

```
public/data/
├── items/
│   ├── item/           # Item icons         → /data/items/item/{nameid}.png
│   ├── collection/     # Item illustrations → /data/items/collection/{nameid}.png
│   └── cards/          # Card art           → /data/items/cards/{nameid}.png
├── icon_jobs/          # Job class icons    → /data/icon_jobs/icon_jobs_{class}.png
├── maps/               # Map previews       → /data/maps/{map}.png
├── monsters/           # Monster sprites    → /data/monsters/{id}.png
└── gameaccount/        # Feature icons (bank, VIP, cash points, etc.)
```

These folders are git-ignored — you manage the files independently.

---

## Optional: Item DB & Mob DB Import

The admin panel includes importers for rAthena databases. Go to **Admin → Item DB** or **Admin → Mob DB** and upload the corresponding file from your rAthena installation:

| Admin section | Files to upload | Location in rAthena |
|---|---|---|
| Item DB (YML) | All `item_db_*.yml` files from your game mode folder — select them all at once. Pre-renewal has 3 files; renewal may have more. | `db/re/` or `db/pre-re/` |
| Item DB (LUA) | `itemInfo.lua` — optional, enriches display names | client `data/luafiles514/lua files/datainfo/` |
| Mob DB | `mob_db.yml` | `db/re/` or `db/pre-re/` |
| Map DB — Cache | `map_cache.dat` — imports map dimensions (width/height) | `db/map_cache.dat` |
| Map DB — Spawns | One or more `mob_db_*.yml` or spawn files | `db/re/` or `db/pre-re/` |
| Map DB — Info | `mapInfo.lua` — optional, enriches map display names | client `data/luafiles514/lua files/datainfo/` |

Once both are imported, **Admin → MvP Cards** auto-discovers MVP, Boss, and Normal monster cards and lets you toggle which ones are visible on the public page.

---

## Features

### Player dashboard
- Register a master (panel) account and link it to existing rAthena accounts via a claim token
- Create new rAthena game accounts (up to the configured limit)
- Change game account password and gender
- Two-factor authentication (TOTP / authenticator app)
- View characters with job, stats, equipment, inventory, cart, and storage
- Reset character position and appearance
- Mobile-responsive interface — installable as a PWA on Android and iOS

### Public pages
- **News** — with reactions and comments
- **Downloads** — categorized file downloads
- **Who Sell** — search the vending market
- **Item DB** — searchable item database with a full-detail modal
- **MVP Cards** — cards currently in the server with quantities and properties

### Admin panel
- Manage master accounts and game accounts (ban, group, VIP)
- Moderate news, comments, and downloads
- Import Item DB and Mob DB from rAthena YAML files
- Configure MVP Card visibility per card
- View action logs and a live server console

---

## Considerations

- **rAthena only** — Hercules is not supported at this time. Support is planned for a future release.
- **Game mode** — Renewal is the primary supported mode. Pre-renewal is functional but some logic is still being refined.
- **Same host not required** — the panel DB and rAthena DB can be on different servers as long as the credentials are reachable.
- **Never run `migrate:fresh` in production** — migrations only create panel tables, but it is good practice anyway.
- **MD5 passwords** — `RA_USE_MD5_PASSWORDS=true` matches rAthena's default. If your server uses plain-text passwords, set it to `false`.
- **Email verification** — disabled by default (`RA_REQUIRE_EMAIL_VERIFY=false`). Enable it and configure a mail driver in `.env` if you want verified accounts.
- **DB prefix** — all panel tables use the `ra_` prefix. Do not write `ra_` in your code; Laravel applies it automatically via the connection config. Writing `DB::table('ra_users')` would produce `ra_ra_users`.

---

## Development Commands

```bash
npm run dev          # Vite dev server with HMR
npm run build        # Production build

php artisan serve                   # PHP dev server
php artisan migrate                 # Run pending migrations
php artisan cache:clear             # Clear application cache
php artisan config:clear            # Clear config cache (after .env changes)
php artisan test                    # Run test suite
```

---

## License

MIT
