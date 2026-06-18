<p align="center">
  <img src="https://rapanel.playgame.cl/images/logo.png" alt="rApanel" width="280" />
</p>

<p align="center">
  <a href="README.md">English</a> · <a href="README-ES.md">Español</a> · <a href="README-PT.md">Português</a> · <a href="README-FR.md">Français</a>
</p>

---

# rApanel

Panneau de contrôle web pour les serveurs **rAthena** de Ragnarok Online. Les joueurs créent un compte maître sur le panneau et gèrent leurs comptes de jeu, personnages et stockage depuis un tableau de bord. Les administrateurs disposent d'un back-office complet pour les utilisateurs, comptes, actualités, téléchargements, Item DB, Mob DB et MVP Cards.

**Stack :** Laravel 12 · Inertia.js · Vue 3 · Tailwind CSS · MySQL / MariaDB

---

## Prérequis

| Prérequis | Minimum |
|---|---|
| PHP | 8.2+ |
| Node.js | 20+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| MariaDB | 10.6+ (alternative à MySQL) |
| rAthena | N'importe quelle version récente |

> Le panneau doit avoir accès réseau à la base de données rAthena et aux ports login/char/map pour que la vérification du statut du serveur fonctionne en temps réel.

---

## Installation

### Installation automatique (recommandée)

Pour Ubuntu 24.04 LTS avec Nginx, exécutez :

```bash
curl -o ~/install.sh https://rapanel-dev.github.io/install.sh && chmod +x ~/install.sh && sudo ~/install.sh
```

L'installateur configure PHP 8.4, Nginx, Node.js, Redis, Supervisor, Composer et rApanel en une seule étape.

Pour mettre à jour après l'installation :

```bash
sudo bash /var/www/rapanel/update.sh
```

Le script de mise à jour place l'application en mode maintenance, télécharge le dernier code, exécute les migrations en attente, recompile les assets du frontend, redémarre les workers de file d'attente et PHP-FPM, puis remet l'application en ligne automatiquement.

### Installation manuelle

#### 1. Cloner le dépôt

```bash
git clone https://github.com/rapanel-dev/rapanel.git
cd rapanel
```

#### 2. Installer les dépendances

```bash
composer install
npm install
```

#### 3. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Ouvrez `.env` et remplissez les sections décrites ci-dessous.

#### 4. Exécuter les migrations

Les migrations ne touchent que la **base de données du panneau** (tables `ra_*`). Elles ne modifient jamais la base de données rAthena.

```bash
php artisan migrate
```

#### 5. Compiler les assets du frontend

```bash
# Production
npm run build

# Développement (HMR)
npm run dev
```

#### 6. Créer le premier administrateur

Inscrivez-vous normalement sur le panneau, puis promouvez l'utilisateur directement en base de données :

```sql
UPDATE ra_users SET role = 'admin' WHERE email = 'votre@email.com';
```

---

## Configuration de l'environnement

### Base de données du panneau (obligatoire)

Le panneau crée ses propres tables avec le préfixe `ra_` dans cette base de données.

```env
DB_CONNECTION=mysql       # mysql ou mariadb — toutes les connexions utilisent ce driver automatiquement
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rapanel
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

### Base de données principale rAthena (obligatoire)

Pointe vers la base de données rAthena (`login`, `char`, `inventory`, etc.).

```env
DB_MAIN_DATABASE=ragnarok
DB_MAIN_USERNAME=votre_utilisateur
DB_MAIN_PASSWORD=votre_mot_de_passe
# DB_MAIN_HOST et DB_MAIN_PORT héritent des valeurs ci-dessus par défaut
```

> **Important :** Si rApanel et la base de données rAthena sont sur des **serveurs différents**, la base rAthena doit autoriser les connexions distantes depuis l'IP du serveur rApanel — accordez les privilèges nécessaires dans MySQL/MariaDB et vérifiez que `bind-address` n'est pas restreint à `127.0.0.1`. Sur le **même serveur** aucune configuration supplémentaire n'est nécessaire.

### Bases de données log et web rAthena (optionnelles)

```env
DB_LOG_DATABASE=ragnarok_log
DB_WEB_DATABASE=ragnarok_web
```

### Configuration du serveur rAthena

```env
RA_SERVER_NAME="Mon Serveur RO"

# Émulateur et mode de jeu
RA_EMULATOR=rathena           # seul rathena est supporté pour le moment
RA_GAME_MODE=renewal          # renewal | pre-renewal — les deux modes sont entièrement supportés

# Règles de compte
RA_MAX_GAME_ACCOUNTS=3        # maximum de comptes de jeu par compte maître
RA_ACCOUNT_NOCASE=true        # userids insensibles à la casse (défaut rAthena)
RA_USE_MD5_PASSWORDS=true     # mots de passe hachés MD5 (défaut rAthena)
RA_REQUIRE_EMAIL_VERIFY=false # activer la vérification d'e-mail

# Multiplicateurs d'EXP — même format Note2 que battle/exp.conf de rAthena (100 = ×1, 1000 = ×10)
RA_BASE_EXP_RATE=1000
RA_JOB_EXP_RATE=1000
RA_MVP_EXP_RATE=300

# IPs et ports du serveur (pour la vérification de statut en direct)
RA_LOGIN_IP=127.0.0.1
RA_LOGIN_PORT=6900
RA_CHAR_IP=127.0.0.1
RA_CHAR_PORT=6121
RA_MAP_IP=127.0.0.1
RA_MAP_PORT=5121

# Carte de destination lors du reset de position du personnage
RA_RESET_MAP=prontera
RA_RESET_X=150
RA_RESET_Y=180

# Fonctionnalités optionnelles
RA_VIP_ENABLED=false
RA_BANK_ENABLED=false
RA_CASHPOINTS_ENABLED=false

# Chemin vers le fichier log du serveur rAthena (console admin)
RA_LOG_PATH=

# Console en direct de l'admin — ws-server (requis pour l'onglet Live Console)
RA_WS_PORT=3001                 # port d'écoute du ws-server (défaut 3001)
RA_WS_SECRET=                   # secret partagé entre rapanel et le ws-server
# Remplacez les URLs auto-détectées uniquement si nécessaire :
# RA_WS_INTERNAL_URL=http://127.0.0.1:3001   # Laravel → ws-server (côté serveur, toujours http://)
# RA_WS_PUBLIC_URL=ws://ip:3001              # navigateur → ws-server (ws:// pour HTTP, wss://domaine/ws-proxy pour HTTPS)

# RoBrowser — si configuré, affiche la bannière "Jouer dans le Navigateur" sur le tableau de bord et le bouton Play Now dans le header
RA_ROBROWSER_URL=
```

### Sécurité (optionnel)

```env
# Authentification à deux facteurs (TOTP / application authentificatrice)
RA_2FA_ENABLED=false        # permettre à n'importe quel utilisateur d'activer le 2FA depuis son profil
RA_2FA_FORCE_ADMINS=false   # obliger tous les admins à configurer le 2FA avant d'accéder au panneau admin

# Déconnexion automatique après inactivité (minutes ; 0 = désactivé)
RA_INACTIVITY_TIMEOUT=30
```

### Intégration Discord (optionnel)

Affiche un widget Discord sur la page d'accueil avec le nombre de membres et d'utilisateurs en ligne en temps réel.

```env
DISCORD_BOT_TOKEN=      # token du bot — créez une app sur discord.com/developers/applications
DISCORD_SERVER_ID=      # clic droit sur votre serveur Discord → Copier l'ID
DISCORD_INVITE_URL=     # lien d'invitation affiché comme bouton "Rejoindre"
```

### Géolocalisation (optionnel)

Affiche une carte mondiale avec la distribution des joueurs sur le tableau de bord admin. Utilise la base de données gratuite MaxMind GeoLite2.

```env
GEOLOCATION_DRIVER=maxmind_database
MAXMIND_USER_ID=        # depuis votre compte MaxMind
MAXMIND_LICENSE_KEY=    # depuis votre compte MaxMind
```

Après avoir ajouté les identifiants, exécutez `php artisan location:update` pour télécharger la base de données locale.

---

## Architecture de la base de données

Le panneau utilise une conception à **double base de données** :

| Connexion | Préfixe | Rôle |
|---|---|---|
| `mysql` | `ra_` | Tables du panneau (`ra_users`, `ra_sessions`, `ra_news`, …) |
| `mysql_main` | — | Tables rAthena (`login`, `char`, `inventory`, …) |
| `mysql_log` | — | Tables de log rAthena |
| `mysql_web` | — | Tables web rAthena |

Le code du panneau utilise toujours `DB::table('users')` (sans préfixe dans le code — Laravel applique `ra_` automatiquement). Les requêtes vers les tables rAthena utilisent `DB::connection('mysql_main')`.

---

## Optionnel : Assets du jeu

Le panneau peut afficher des icônes, illustrations, images de cartes et plus encore si vous les placez dans `public/data/` :

```
public/data/
├── items/
│   ├── item/           # Icônes d'items          → /data/items/item/{nameid}.png
│   ├── collection/     # Illustrations d'items   → /data/items/collection/{nameid}.png
│   └── cards/          # Art de cartes           → /data/items/cards/{nameid}.png
├── icon_jobs/          # Icônes de classe/métier  → /data/icon_jobs/icon_jobs_{class}.png
├── maps/               # Aperçus de cartes        → /data/maps/{map}.png
├── monsters/           # Sprites de monstres      → /data/monsters/{id}.gif
└── gameaccount/        # Icônes de fonctions (banque, VIP, cash points, etc.)
```

---

## Optionnel : Importer Item DB et Mob DB

Le panneau inclut des importateurs pour les bases de données YAML et LUA de rAthena. Accédez à **Admin → Item DB** ou **Admin → Mob DB** et téléversez le fichier correspondant depuis votre installation rAthena :

| Section admin | Fichiers à téléverser | Emplacement dans rAthena |
|---|---|---|
| Item DB (YML) | Tous les fichiers `item_db_*.yml` du dossier de votre mode de jeu — les sélectionner tous en même temps. Pre-renewal en a 3 ; renewal peut en avoir davantage. | `db/re/` ou `db/pre-re/` |
| Item DB (LUA) | `itemInfo.lua` — optionnel, enrichit les noms d'affichage | client `data/luafiles514/lua files/datainfo/` |
| Mob DB | `mob_db.yml` | `db/re/` ou `db/pre-re/` |
| Map DB — Cache | `map_cache.dat` — importe les dimensions des cartes | `db/map_cache.dat` |
| Map DB — Spawns | Un ou plusieurs fichiers de spawn | `db/re/` ou `db/pre-re/` |
| Map DB — Info | `mapInfo.lua` — optionnel, enrichit les noms d'affichage des cartes | client `data/luafiles514/lua files/datainfo/` |

Une fois les deux importés, **Admin → MvP Cards** découvre automatiquement les cartes de monstres MVP, Boss et Normal, et permet d'activer lesquelles sont visibles sur la page publique.

---

## Fonctionnalités

### Tableau de bord du joueur
- Créer un compte maître et le lier à des comptes rAthena existants via un token de réclamation
- Créer de nouveaux comptes de jeu rAthena (jusqu'à la limite configurée)
- Modifier le mot de passe et le genre du compte de jeu
- Authentification à deux facteurs (TOTP / application authentificatrice)
- Voir les personnages avec classe, stats, équipements, inventaire, chariot et stockage
- Réinitialiser la position et l'apparence du personnage
- Interface responsive mobile — installable comme PWA sur Android et iOS

### Pages publiques
- **Actualités** — avec réactions et commentaires
- **Téléchargements** — fichiers catégorisés ; les admins peuvent déposer des patches pour les mises à jour du client
- **Who Sell** — recherche sur le marché des vendings
- **Item DB** — base de données d'items avec modal de détail complet
- **MVP Cards** — cartes actives sur le serveur avec quantités et propriétés
- **Wiki** — base de connaissances style docs, organisée en sections et articles

### Panneau d'administration
- Gérer les comptes maîtres et de jeu (ban, groupe, VIP)
- Modérer les actualités, commentaires et téléchargements (y compris les fichiers patch pour les mises à jour du client)
- Importer Item DB et Mob DB depuis des fichiers YAML/LUA rAthena
- Configurer la visibilité des MVP Cards par carte
- Gérer les sections et articles du Wiki (CRUD)
- Consulter les logs d'actions et la console du serveur en direct
- Carte de géolocalisation des joueurs (nécessite MaxMind GeoLite2)

---

## Considérations

- **rAthena uniquement** — Hercules n'est pas supporté pour le moment. Le support est prévu dans une future version.
- **Mode de jeu** — Les deux modes `renewal` et `pre-renewal` sont entièrement supportés. Configurez `RA_GAME_MODE` dans `.env` selon votre serveur.
- **Hôtes différents autorisés** — la base du panneau et celle de rAthena peuvent être sur des serveurs distincts, tant que les identifiants sont accessibles.
- **Fuseau horaire** — `APP_TIMEZONE` dans `.env` doit correspondre au fuseau horaire du système d'exploitation et de MariaDB/MySQL. Si vous le modifiez, exécutez également `sudo timedatectl set-timezone <timezone>` sur le serveur ; sinon les dates seront enregistrées dans le mauvais fuseau.
- **Ne jamais exécuter `migrate:fresh` en production** — les migrations ne créent que des tables du panneau, mais c'est une bonne pratique de toute façon.
- **Mots de passe MD5** — `RA_USE_MD5_PASSWORDS=true` correspond au défaut de rAthena. Si votre serveur utilise des mots de passe en clair, mettez-le à `false`.
- **Vérification d'e-mail** — désactivée par défaut. Activez-la et configurez un driver mail dans `.env` si vous souhaitez des comptes vérifiés.
- **Préfixe DB** — toutes les tables du panneau utilisent le préfixe `ra_`. N'écrivez pas `ra_` dans le code ; Laravel l'applique automatiquement. Écrire `DB::table('ra_users')` produirait `ra_ra_users`.

---

## Commandes de développement

```bash
npm run dev          # Serveur Vite avec HMR
npm run build        # Build de production

php artisan serve                   # Serveur PHP de développement
php artisan migrate                 # Exécuter les migrations en attente
php artisan cache:clear             # Vider le cache de l'application
php artisan config:clear            # Vider le cache de config (après modifications du .env)
php artisan test                    # Exécuter la suite de tests
```

---

## Licence

MIT
