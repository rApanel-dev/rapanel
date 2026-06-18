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

> **The panel must have network access to the rAthena database.** Without it, migrations will fail and the panel will not work. If both are on the same server, no extra configuration is needed. If they are on different servers, see the [DB access note](#rathena-main-database-required) in the configuration section.
>
> Network access to the login/char/map ports is optional — it is only needed for the live server-status indicator on the home page.

---

## Installation

### Automatic installer (recommended)

For Ubuntu 24.04 LTS with Nginx, run:

```bash
curl -o ~/install.sh https://rapanel-dev.github.io/install.sh && chmod +x ~/install.sh && sudo ~/install.sh
```

The installer sets up PHP 8.4, Nginx, Node.js, Redis, Supervisor, Composer and rApanel in a single step.

To update after installation:

```bash
sudo bash /var/www/rapanel/update.sh
```

The update script puts the app in maintenance mode, pulls the latest code, runs pending migrations, rebuilds frontend assets, restarts queue workers and PHP-FPM, then brings the app back online automatically.

### Manual installation

#### 1. Clone the repository

```bash
git clone https://github.com/rapanel-dev/rapanel.git
cd rapanel
```

#### 2. Install dependencies

```bash
composer install
npm install
```

#### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and fill in the sections below.

#### 4. Run migrations

Migrations only touch the **panel database** (`ra_*` tables). They never modify the rAthena database.

```bash
php artisan migrate
```

#### 5. Build frontend assets

```bash
# Production
npm run build

# Development (HMR)
npm run dev
```

#### 6. Create the first admin

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

> **Important:** If rApanel and the rAthena database are on **different servers**, the rAthena database must allow remote connections from the rApanel server's IP — grant the appropriate privileges in MySQL/MariaDB and make sure `bind-address` is not restricted to `127.0.0.1`. On the **same server** no extra configuration is needed.

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
RA_GAME_MODE=renewal          # renewal | pre-renewal — both modes are fully supported

# Account rules
RA_MAX_GAME_ACCOUNTS=3        # max game accounts per master account
RA_ACCOUNT_NOCASE=true        # case-insensitive userids (rAthena default)
RA_USE_MD5_PASSWORDS=true     # MD5-hashed passwords (rAthena default)
RA_REQUIRE_EMAIL_VERIFY=false # toggle email verification

# EXP multipliers — same Note2 format as rAthena's battle/exp.conf (100 = ×1, 1000 = ×10)
RA_BASE_EXP_RATE=1000
RA_JOB_EXP_RATE=1000
RA_MVP_EXP_RATE=300

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

# Admin live console — ws-server (required for Live Console tab)
RA_WS_PORT=3001                 # port where ws-server listens (default 3001)
RA_WS_SECRET=                   # shared secret between rapanel and ws-server
# Override auto-detected URLs only when needed:
# RA_WS_INTERNAL_URL=http://127.0.0.1:3001   # Laravel → ws-server (server-side, always http://)
# RA_WS_PUBLIC_URL=ws://ip:3001              # browser → ws-server (ws:// for HTTP, wss://domain/ws-proxy for HTTPS)

# RoBrowser — if set, shows a "Play in Browser" banner on the dashboard and a Play Now button in the header
RA_ROBROWSER_URL=
```

### Security features (optional)

```env
# Two-factor authentication (TOTP / authenticator app)
RA_2FA_ENABLED=false        # allow any user to enable 2FA voluntarily from their profile
RA_2FA_FORCE_ADMINS=false   # require all admins to configure 2FA before accessing the admin panel

# Auto-logout after inactivity (minutes; 0 = disabled)
RA_INACTIVITY_TIMEOUT=30
```

### Discord integration (optional)

Displays a Discord widget on the Home page with live member and online counts.

```env
DISCORD_BOT_TOKEN=      # bot token — create an app at discord.com/developers/applications
DISCORD_SERVER_ID=      # right-click your server in Discord → Copy ID
DISCORD_INVITE_URL=     # invite link shown as the "Join" button
```

### Geolocation (optional)

Shows a world map with player distribution on the admin dashboard. Uses the free MaxMind GeoLite2 database.

```env
GEOLOCATION_DRIVER=maxmind_database
MAXMIND_USER_ID=        # from your MaxMind account
MAXMIND_LICENSE_KEY=    # from your MaxMind account
```

After adding the credentials, run `php artisan location:update` to download the local database.

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
├── monsters/           # Monster sprites    → /data/monsters/{id}.gif
└── gameaccount/        # Feature icons (bank, VIP, cash points, etc.)
```

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
- **Downloads** — categorized file downloads; admins can upload patch files for client auto-updaters
- **Who Sell** — search the vending market
- **Item DB** — searchable item database with a full-detail modal
- **MVP Cards** — cards currently in the server with quantities and properties
- **WOE Schedule** — live banner on the home page showing active and upcoming War of Emperium events (WOE 1 FE / WOE 2 SE / WOE TE); full public schedule page
- **Wiki** — docs-style knowledge base organized in sections and articles

### Admin panel
- Manage master accounts and game accounts (ban, group, VIP)
- Moderate news, comments, and downloads (including patch files for client updaters)
- Import Item DB and Mob DB from rAthena YAML files
- Configure MVP Card visibility per card
- Manage WOE schedules (WOE 1 FE / WOE 2 SE / WOE TE) with castle, timetable, image, and enable/disable toggle
- Manage wiki sections and articles (CRUD)
- View action logs and a live server console
- Player geolocation map (requires MaxMind GeoLite2)

---

## Considerations

- **rAthena only** — Hercules is not supported at this time. Support is planned for a future release.
- **Game mode** — Both `renewal` and `pre-renewal` modes are fully supported. Set `RA_GAME_MODE` in `.env` to match your server.
- **Same host not required** — the panel DB and rAthena DB can be on different servers as long as the credentials are reachable.
- **Timezone** — `APP_TIMEZONE` in `.env` must match the OS timezone and MariaDB/MySQL timezone. If you change it, also run `sudo timedatectl set-timezone <timezone>` on the server, otherwise timestamps will be stored in the wrong timezone.
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

This project is open source, released under the [MIT License](LICENSE).
