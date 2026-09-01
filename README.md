# Dzhura D2 (Discord Bot)

**Dzhura D2** is a feature-rich, high-performance Discord bot built with **PHP 8.1+** on the [Laravel Zero](https://laravel-zero.com/) framework using [`team-reflex/discord-php`](https://github.com/discord-php/DiscordPHP). 

Designed primarily for gaming communities with dedicated support for **Destiny 2** and **Helldivers 2**, Dzhura D2 automates Destiny 2 LFG (Looking For Group) sessions, manages dynamic voice channels, tracks member activity through a leveling system, and provides guild-level logging and administrative configurations.

---

## ✨ Features

### 🎮 **Destiny 2 LFG (Looking For Group) System**
* **Tailored for Destiny 2:** Built specifically for Destiny 2 Raids, PVP (Crucible/Trials), PVE (Nightfalls/Dungeons), Gambit, and custom fireteam activities.
* **Interactive Event Posts:** Create, edit, and cancel LFG sessions via slash commands (`/lfg`).
* **Approval Modes:** Supports both **Automatic** approval and **Manual Approval** via private creator DMs.
* **Participant Queues & Reserves:** Manages primary fireteam slots and backup reserve queues automatically.
* **Dynamic Voice Channel Provisioning:** Provisions temporary voice channels bound directly to created LFGs.
* **Auto-Cleanup:** Deletes expired or cancelled LFG posts and associated temporary voice channels.
* 📖 **Detailed Guide:** See [Destiny 2 LFG System Documentation](docs/lfg.md) for full architectural details.

### ⚔️ **Helldivers 2 Support**
* **Dedicated LFG Builder:** Interactive panel (`/helldivers lfg`) to build 4-player Helldivers fireteams.
* **Target Role Pings:** Select enemy faction roles (e.g. Terminids, Automatons) and difficulty level roles (e.g. Helldive) to ping candidates.
* **Real-time Voice Roster Tracking:** Dynamically updates squad slots (`1`, `2`, `3`, `4`) in message embeds as members join or leave the voice channel.
* **Background Voice Cleaner:** Automated background cleaner checks empty channels every 60 seconds and auto-deletes abandoned channels exceeding server timeout (`HelldiversVoiceChannelCleaner`).
* 📖 **Detailed Guide:** See [Helldivers 2 System Documentation](docs/helldivers.md) for full architectural details.

### 📈 **Activity Leveling System**
* **XP & Levels:** Automatically rewards user text interactions with XP points across 100 levels.
* **Anti-Spam & Multipliers:** Features a 1-minute anti-spam cooldown and role-based XP multipliers.
* **Role Rewards & Announcements:** Automated role assignments (stacking or replacement modes) and customizable level-up announcements.
* **Level Slash Commands:** Interactive `/levels rank`, paginated `/levels leaderboard`, `/levels table`, and admin XP management (`/levels give-xp`, `/levels remove-xp`).
* 📖 **Detailed Guide:** See [Leveling System Documentation](docs/level.md) for full architectural details.

### 🔊 **Dynamic Voice Channel Control**
* **Slash Commands:** `/voicecreate`, `/voiceedit`, and `/voicedelete` for flexible dynamic voice channel management.
* **Role Permissions & Limits:** Enforces server `permittedRoles` and per-user active `channelLimit`.
* **Modal Management:** Interactive modal form editing for channel names and user capacity limits.
* 📖 **Detailed Guide:** See [Dynamic Voice Channel Documentation](docs/vc.md) for full architectural details.

### 🛡️ **Server Logging & Guild Settings**
* **Audit Tracker:** Monitors guild events, member updates, voice states, and bans using `ServerLogsService`.
* **Configurable Guild Settings (`/settings`):** Interactive, embed-driven administrator command manager for global timezones, voice channels, leveling rules, LFG pings, Helldivers options, and server logs.
* 📖 **Detailed Guide:** See [Settings Management System Documentation](docs/settings.md) for full architectural details.

---

## 🛠️ Requirements

* **PHP:** `^8.1` (with `pdo`, `mbstring`, `curl`, and `json` extensions)
* **Composer**
* **Database:** MariaDB / MySQL or SQLite
* **Discord Application Bot Token** with `GUILD_MEMBERS` intent enabled.

---

## 🚀 Quick Start

### 1. Repository Setup & Dependencies

Clone the repository and install PHP dependencies via Composer:

```bash
git clone https://github.com/your-username/dzhura_d2.git
cd dzhura_d2
composer install
```

### 2. Environment Configuration

Copy the example environment file and set up your environment variables:

```bash
cp .env.example .env
```

Edit `.env` to configure your Discord bot token and database credentials:

```env
DISCORD_TOKEN=your_discord_bot_token_here
APP_VERSION=1.0.0

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=d2
DB_USERNAME=d2
DB_PASSWORD=d2
```

### 3. Database Migration

Run database migrations to initialize the schema:

```bash
php d2 migrate
```

### 4. Run the Bot

Start the Discord bot process using the CLI binary:

```bash
php d2 run
```

---

## 🐳 Docker Deployment

The project includes a ready-to-use Docker environment with MariaDB.

### Using Docker Compose

1. Configure your `.env` file.
2. Start the services in detached mode:

```bash
docker-compose up -d --build
```

3. Run migrations inside the container:

```bash
docker-compose exec app php d2 migrate
```

---

## 📂 Project Architecture & Documentation

```
dzhura_d2/
├── docs/
│   ├── lfg.md                  # Destiny 2 LFG System Documentation
│   ├── level.md                # Activity Leveling System Documentation
│   ├── helldivers.md           # Helldivers 2 System Documentation
│   ├── vc.md                   # Dynamic Voice Channel System Documentation
│   └── settings.md             # Settings Management System Documentation
├── app/
│   ├── Commands/               # Console commands (Run.php)
│   ├── Discord/
│   │   ├── Helpers/            # Helper utilities (SlashCommandHelper)
│   │   ├── Services/           # Server audit logging & services
│   │   └── SlashCommands/      # Discord Slash Command handlers
│   │       ├── Helldivers/     # Helldivers 2 specific commands & cleaner
│   │       ├── Levels/         # Leveling system logic & commands
│   │       ├── Lfg/            # LFG listeners, activity types, & commands
│   │       └── Settings/       # Server configuration commands & factories
│   ├── Lfg.php                 # Eloquent Models
│   ├── Level.php
│   ├── VoiceChannel.php
│   ├── HelldiversLfgVoiceChannel.php
│   └── Setting.php
├── config/                     # Application & Discord configs
├── database/                   # Migrations & database schemas
├── docker-compose.yaml         # Container orchestration
├── Dockerfile                  # Container build instructions
└── d2                          # Laravel Zero CLI entrypoint
```

---

## 🧪 Testing

Run test suites using [Pest PHP](https://pestphp.com/):

```bash
./vendor/bin/pest
```

---

## 📜 License

This project is licensed under the [MIT License](LICENSE).
