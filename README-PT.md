<p align="center">
  <img src="https://rapanel.playgame.cl/images/logo.png" alt="rApanel" width="280" />
</p>

<p align="center">
  <a href="README.md">English</a> · <a href="README-ES.md">Español</a> · <a href="README-PT.md">Português</a> · <a href="README-FR.md">Français</a>
</p>

---

# rApanel

Painel de controle web para servidores **rAthena** de Ragnarok Online. Os jogadores registram uma conta mestre no painel e gerenciam suas contas de jogo, personagens e armazenamento a partir de um dashboard. Os administradores contam com um back-office completo para usuários, contas, notícias, downloads, Item DB, Mob DB e MVP Cards.

**Stack:** Laravel 12 · Inertia.js · Vue 3 · Tailwind CSS · MySQL / MariaDB

---

## Requisitos

| Requisito | Mínimo |
|---|---|
| PHP | 8.2+ |
| Node.js | 20+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| MariaDB | 10.6+ (alternativa ao MySQL) |
| rAthena | Qualquer build recente |

> O painel deve ter acesso de rede ao banco de dados do rAthena e às portas login/char/map para que o indicador de status do servidor funcione em tempo real.

---

## Instalação

### 1. Clonar o repositório

```bash
git clone https://github.com/KhrizPlayCL/rapanel.git
cd rapanel
```

### 2. Instalar dependências

```bash
composer install
npm install
```

### 3. Configurar o ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Abra `.env` e preencha as seções descritas abaixo.

### 4. Executar as migrations

As migrations tocam apenas o **banco de dados do painel** (tabelas `ra_*`). Nunca modificam o banco de dados do rAthena.

```bash
php artisan migrate
```

### 5. Compilar os assets do frontend

```bash
# Produção
npm run build

# Desenvolvimento (HMR)
npm run dev
```

### 6. Criar o primeiro administrador

Registre-se normalmente no painel e depois promova o usuário diretamente no banco de dados:

```sql
UPDATE ra_users SET role = 'admin' WHERE email = 'seu@email.com';
```

---

## Configuração do ambiente

### Banco de dados do painel (obrigatório)

O painel cria suas próprias tabelas com prefixo `ra_` neste banco de dados.

```env
DB_CONNECTION=mysql       # mysql ou mariadb — todas as conexões usam este driver automaticamente
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rapanel
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### Banco de dados principal do rAthena (obrigatório)

Aponta para o banco de dados do rAthena (`login`, `char`, `inventory`, etc.).

```env
DB_MAIN_DATABASE=ragnarok
DB_MAIN_USERNAME=seu_usuario
DB_MAIN_PASSWORD=sua_senha
# DB_MAIN_HOST e DB_MAIN_PORT herdam os valores acima por padrão
```

### Bancos de dados log e web do rAthena (opcionais)

```env
DB_LOG_DATABASE=ragnarok_log
DB_WEB_DATABASE=ragnarok_web
```

### Configuração do servidor rAthena

```env
RA_SERVER_NAME="Meu Servidor RO"

# Emulador e modo de jogo
RA_EMULATOR=rathena           # apenas rathena é suportado no momento
RA_GAME_MODE=renewal          # renewal (estável) | pre-renewal (em desenvolvimento)

# Regras de conta
RA_MAX_GAME_ACCOUNTS=3        # máximo de contas de jogo por conta mestre
RA_ACCOUNT_NOCASE=true        # userids sem distinção de maiúsculas (padrão rAthena)
RA_USE_MD5_PASSWORDS=true     # senhas com hash MD5 (padrão rAthena)
RA_REQUIRE_EMAIL_VERIFY=false # ativar verificação de e-mail

# IPs e portas do servidor (para verificação de status em tempo real)
RA_LOGIN_IP=127.0.0.1
RA_LOGIN_PORT=6900
RA_CHAR_IP=127.0.0.1
RA_CHAR_PORT=6121
RA_MAP_IP=127.0.0.1
RA_MAP_PORT=5121

# Mapa de destino ao resetar posição do personagem
RA_RESET_MAP=prontera
RA_RESET_X=150
RA_RESET_Y=180

# Funcionalidades opcionais
RA_VIP_ENABLED=false
RA_BANK_ENABLED=false
RA_CASHPOINTS_ENABLED=false

# Caminho para o log do servidor rAthena (console no admin)
RA_LOG_PATH=
```

---

## Arquitetura do banco de dados

O painel usa um design de **banco de dados duplo**:

| Conexão | Prefixo | Propósito |
|---|---|---|
| `mysql` | `ra_` | Tabelas do painel (`ra_users`, `ra_sessions`, `ra_news`, …) |
| `mysql_main` | — | Tabelas do rAthena (`login`, `char`, `inventory`, …) |
| `mysql_log` | — | Tabelas de log do rAthena |
| `mysql_web` | — | Tabelas web do rAthena |

O código do painel sempre usa `DB::table('users')` (sem prefixo no código — o Laravel aplica `ra_` automaticamente). Consultas às tabelas do rAthena usam `DB::connection('mysql_main')`.

---

## Opcional: Assets do jogo

O painel pode exibir ícones, ilustrações e imagens de cartas se você colocá-los em `public/data/`:

```
public/data/
├── items/
│   ├── icons/          # Ícones de itens    → /data/items/icons/{item_id}.png
│   ├── images/         # Imagens de itens   → /data/items/images/{item_id}.png
│   └── cards/          # Arte de cartas     → /data/items/cards/{item_id}.png
└── gameaccount/
    └── job_icons/      # Ícones de classe/trabalho
```

Essas pastas estão no `.gitignore` — os arquivos são gerenciados de forma independente.

---

## Opcional: Importar Item DB e Mob DB

O painel inclui importadores para os bancos de dados YAML e LUA do rAthena. Acesse **Admin → Item DB** ou **Admin → Mob DB** e envie o arquivo correspondente da sua instalação do rAthena:

| Seção admin | Arquivos a enviar | Localização no rAthena |
|---|---|---|
| Item DB (YML) | Todos os arquivos `item_db_*.yml` da pasta do seu modo de jogo — selecionar todos ao mesmo tempo. Pre-renewal tem 3 arquivos; renewal pode ter mais. | `db/re/` ou `db/pre-re/` |
| Item DB (LUA) | `itemInfo.lua` — opcional, enriquece os nomes de exibição | cliente `data/luafiles514/lua files/datainfo/` |
| Mob DB | `mob_db.yml` | `db/re/` ou `db/pre-re/` |

Com ambos importados, **Admin → MvP Cards** descobre automaticamente as cartas de monstros MVP, Boss e Normal, e permite ativar quais ficam visíveis na página pública.

---

## Funcionalidades

### Dashboard do jogador
- Registrar uma conta mestre e vinculá-la a contas rAthena existentes via token de reivindicação
- Criar novas contas de jogo rAthena (até o limite configurado)
- Alterar senha e gênero da conta de jogo
- Visualizar personagens com classe, stats, equipamentos, inventário, carrinho e armazenamento
- Resetar posição e aparência do personagem

### Páginas públicas
- **Notícias** — com reações e comentários
- **Downloads** — arquivos categorizados
- **Who Sell** — busca no mercado de vendings
- **Item DB** — banco de dados de itens com modal de detalhes completo
- **MVP Cards** — cartas ativas no servidor com quantidades e propriedades

### Painel de administração
- Gerenciar contas mestres e de jogo (ban, grupo, VIP)
- Moderar notícias, comentários e downloads
- Importar Item DB e Mob DB de arquivos YAML/LUA do rAthena
- Configurar visibilidade de MVP Cards por carta
- Visualizar logs de ações e console do servidor em tempo real

---

## Considerações

- **Apenas rAthena** — Hercules não é suportado no momento. O suporte está planejado para uma versão futura.
- **Modo de jogo** — Renewal é o modo principal suportado. Pre-renewal é funcional, mas a lógica ainda está sendo refinada.
- **Hosts diferentes são permitidos** — o banco do painel e o do rAthena podem estar em servidores distintos, desde que as credenciais sejam acessíveis.
- **Nunca execute `migrate:fresh` em produção** — as migrations só criam tabelas do painel, mas é uma boa prática de qualquer forma.
- **Senhas MD5** — `RA_USE_MD5_PASSWORDS=true` corresponde ao padrão do rAthena. Se seu servidor usa senhas em texto puro, defina como `false`.
- **Verificação de e-mail** — desativada por padrão. Ative e configure um driver de e-mail no `.env` se quiser contas verificadas.
- **Prefixo do banco** — todas as tabelas do painel usam o prefixo `ra_`. Não escreva `ra_` no código; o Laravel aplica automaticamente. Escrever `DB::table('ra_users')` produziria `ra_ra_users`.

---

## Comandos de desenvolvimento

```bash
npm run dev          # Servidor Vite com HMR
npm run build        # Build de produção

php artisan serve                   # Servidor PHP de desenvolvimento
php artisan migrate                 # Executar migrations pendentes
php artisan cache:clear             # Limpar cache da aplicação
php artisan config:clear            # Limpar cache de config (após mudanças no .env)
php artisan test                    # Executar suite de testes
```

---

## Licença

MIT
