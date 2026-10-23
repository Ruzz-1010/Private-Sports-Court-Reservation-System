# Windows Setup Guide

## Private Pickleball Court Reservation System

This guide takes a brand-new Windows laptop from nothing to a running copy of the
system. Follow the steps **in order**. Every step ends with a checkpoint — do not
continue until the checkpoint passes.

There are two ways to do this. **Option A (Laravel Herd)** is pure Windows and
graphical, so it is the easiest. **Option B (WSL2)** runs Linux inside Windows and
is what the original machine uses, so it is the most reliable. Pick **one** — do
not mix them.

> **Which terminal?** Every command in **Option A** is written for **CMD** —
> press `Win + R`, type `cmd`, press Enter. That is the black window. You can
> copy and paste into it exactly as printed.
>
> Commands in **Option B** are written for the **Ubuntu** terminal inside WSL,
> because that is a Linux shell. Do not paste Option B commands into CMD.

---

## What you must install

| Software | Version | Why it is needed |
|---|---|---|
| PHP | **8.3 or newer** | Runs the application |
| PHP extensions | `pdo_pgsql`, `gd`, `bcmath`, `intl`, `zip`, `mbstring`, `dom`, `curl`, `openssl`, `fileinfo` | `pdo_pgsql` talks to PostgreSQL, `gd` renders the PDF receipt |
| Composer | 2.x | Installs the PHP packages |
| PostgreSQL | **16** (14+ works) | The database |
| Node.js | **20 or newer** (22 used here) | Builds the CSS and JavaScript |
| npm | comes with Node | Installs the frontend packages |
| Git | any recent | Clones the repository |

Optional but useful: **Visual Studio Code** or **PhpStorm** to read and edit code.

---

# OPTION A — Laravel Herd (pure Windows, easiest)

## A1. Install Git

Download from <https://git-scm.com/download/win> and accept every default.
When the installer asks about "Adjusting your PATH environment", choose
**Git from the command line and also from third-party software** — that is the
default and it is the option that lets you type `git` inside CMD.

Open **CMD** (`Win + R` → `cmd` → Enter) and confirm:

```bat
git --version
```

Checkpoint: it prints a version number.

## A2. Install Laravel Herd

Download from <https://herd.laravel.com/windows> and install it. Herd installs
PHP, Composer, and Node in one go — you do not need to install them separately.

Open a **new** CMD window (`Win + R` → `cmd` → Enter). Herd only adds itself to
the PATH after a new window is opened, so an already-open window will not see it.
Confirm all three:

```bat
php -v
composer -V
node -v
```

Checkpoint: PHP prints `8.3` or higher, Composer prints `2.x`, Node prints `20`
or higher.

## A3. Turn on the PHP extensions

This is the step people skip and then get a confusing error later. Herd ships
most extensions enabled, but you must verify them:

```bat
php -m
```

You need to see these names in the output:

```
bcmath  curl  dom  fileinfo  gd  intl  mbstring  openssl  pdo_pgsql  zip
```

- If `pdo_pgsql` is **missing**, the application cannot talk to PostgreSQL at
  all. In Herd, open **Settings → PHP**, tick `pdo_pgsql`, and save.
- If `gd` is missing, the PDF receipt will fail.

Checkpoint: `php -m` lists every name above.

## A4. Install PostgreSQL

Download the EDB installer from <https://www.postgresql.org/download/windows>.
Accept every default. When asked, remember the **password you type for the
superuser `postgres`** — you need it in a moment. Let the installer keep port
**5432**.

Also install **pgAdmin** when offered — it gives you a graphical way to see the
database, which is handy during the defense.

Checkpoint: pgAdmin opens and shows a server you can connect to.

## A5. Clone the project

Open **CMD** (`Win + R` → `cmd` → Enter) and run these one at a time:

```bat
cd %USERPROFILE%
git clone https://github.com/Ruzz-1010/Private-Sports-Court-Reservation-System.git
cd Private-Sports-Court-Reservation-System
dir
```

> If Git asks for your GitHub username and password, use a **personal access
> token**, not your normal password. GitHub stopped accepting account passwords
> for `git clone` in 2021.

Checkpoint: `dir` lists `artisan`, `composer.json`, `app`, and `routes`.

---

# OPTION B — WSL2 (most reliable, mirrors the original machine)

Use this if Option A gives you trouble, or if you want an environment identical
to the one the project was actually built on.

## B1. Install WSL and Ubuntu

Open **PowerShell as Administrator**:

```powershell
wsl --install
```

Restart the laptop when it asks. After restarting, Ubuntu opens and asks you to
create a Linux username and password (these are **not** your Windows ones —
remember them).

Checkpoint: `lsb_release -a` shows Ubuntu 22.04 or newer.

## B2. Install everything inside Ubuntu

Paste this whole block into the Ubuntu terminal:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl unzip git
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-pgsql php8.3-gd php8.3-bcmath \
  php8.3-intl php8.3-zip php8.3-mbstring php8.3-xml php8.3-curl php8.3-sqlite3
sudo apt install -y postgresql postgresql-contrib
sudo apt install -y nodejs npm
sudo npm install -g npm@latest
```

Install Composer:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Checkpoints:

```bash
php -v          # 8.3.x
composer -V     # 2.x
node -v         # v20 or higher
php -m          # must contain pdo_pgsql, gd, bcmath, intl, zip
psql --version  # 16.x
```

## B3. Start PostgreSQL and clone the project

```bash
sudo service postgresql start
cd ~
git clone https://github.com/Ruzz-1010/Private-Sports-Court-Reservation-System.git
cd Private-Sports-Court-Reservation-System
```

---

# THE PART EVERYONE MUST DO (same for Option A and B)

## Step 1 — Create the database user and database

Open **pgAdmin** (Option A) or the Ubuntu `psql` (Option B) and run this SQL:

```sql
CREATE USER court_user WITH PASSWORD '12345' CREATEDB;
CREATE DATABASE court_reservation OWNER court_user;
```

> **Why `CREATEDB`?** The automated tests create and drop their own database
> schema. Without this permission, `php artisan test` fails with a permission
> error even though the application itself works fine.

Checkpoint: pgAdmin shows a `court_reservation` database.

## Step 2 — Create the `testing` schema (needed only for tests)

This is the single most-missed step. The test suite does **not** use the normal
database — it uses a separate PostgreSQL schema called `testing`. If you skip
this, every test fails with `relation "testing.users" does not exist`.

Connect to the `court_reservation` database and run:

```sql
\c court_reservation
CREATE SCHEMA IF NOT EXISTS testing;
GRANT ALL ON SCHEMA testing TO court_user;
```

In pgAdmin, use the **Query Tool** on the `court_reservation` database instead of
`\c court_reservation`.

Checkpoint: the query returns `CREATE SCHEMA`.

## Step 3 — Create your `.env` file

**In CMD (Option A):**

```bat
copy .env.example .env
```

**In the Ubuntu terminal (Option B) only:**

```bash
cp .env.example .env
```

Then **open `.env` in Notepad and change these lines.** In CMD:

```bat
notepad .env
```

This is where most setup problems come from:

```env
DB_PASSWORD=12345

ADMIN_PASSWORD=admin_dev_12345
```

> ⚠️ **`ADMIN_PASSWORD` is empty in `.env.example` on purpose.** If you leave it
> empty, the admin seeder **silently skips** and you will have no administrator
> account — the login page will work, but no admin screens will exist. Set it.

For a local demo that does not need PayMongo, also set:

```env
PAYMENT_SIMULATION_ENABLED=true
```

This shows a **Simulate payment** button so you can complete the entire booking
flow without a PayMongo account, a public URL, or a webhook. Set it back to
`false` when you want to test the real PayMongo integration.

Leave `APP_URL=http://localhost:8000` and `QUEUE_CONNECTION=sync` as they are.

## Step 4 — Generate the application key

```bat
php artisan key:generate
```

> Do **not** use `openssl rand -base64 24` — that command does not exist on a
> normal Windows install. Let Artisan do it.

Checkpoint: it prints `Application key [...] set successfully`.

## Step 5 — Install the PHP and frontend packages

```bat
composer install
npm install
npm run build
```

Checkpoint: `npm run build` finishes with no errors and creates a
`public/build` folder. **If you skip `npm run build`, every page renders with no
styling and no working buttons** — the most common "it runs but looks broken"
report.

## Step 6 — Build the database

```bat
php artisan migrate
php artisan db:seed
```

Checkpoint: the migrate output lists the tables, and the seed output ends with
`Admin account ready.`

If you instead see `ADMIN_PASSWORD is not set; skipping admin seeder.` — go back
to Step 3. It is not a warning to ignore; it means you have no admin account.

## Step 7 — Run it

```bat
php artisan serve
```

Open <http://127.0.0.1:8000> in your browser.

Checkpoint: the landing page appears with styling. Log in with:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@court.local` | whatever you set in `ADMIN_PASSWORD` |
| Customer | register one from the Register page | — |

`QUEUE_CONNECTION=sync` means **you do not need a queue worker**. Emails are sent
during the request. This is deliberate — it removes a whole terminal your
groupmates would otherwise have to remember to start.

---

## Step 8 — Run the tests (optional but recommended before the defense)

```bat
php artisan test
```

Expected: **131 tests, 332 assertions, all passing.**

If every test errors with `schema "testing" does not exist` — go back to **Step 2**.

---

## Run it every day after setup

Only these three commands, in this order.

**In CMD (Option A):**

```bat
cd %USERPROFILE%\Private-Sports-Court-Reservation-System
php artisan migrate
php artisan serve
```

Then open <http://127.0.0.1:8000>.

**In the Ubuntu terminal (Option B) only** — you must start PostgreSQL first:

```bash
cd ~/Private-Sports-Court-Reservation-System
sudo service postgresql start
php artisan migrate
php artisan serve
```

> Close the CMD window and the site stops. That is normal — the server runs
> inside that window. To stop it, press `Ctrl + C`.

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `could not find driver` or `PDO driver does not exist` | `pdo_pgsql` extension is off | Enable it (Option A: Herd Settings → PHP). Re-check with `php -m` |
| `password authentication failed for user "court_user"` | Wrong password in `.env` | Set `DB_PASSWORD=12345`, or recreate the user with your password |
| `database "court_reservation" does not exist` | Database not created | Run the SQL in Step 1 |
| `extension "btree_gist" does not exist` | Missing PostgreSQL extension | Connect **as the `postgres` superuser** and run `CREATE EXTENSION IF NOT EXISTS btree_gist;` |
| `relation "testing.users" does not exist` | Testing schema missing | Run Step 2 |
| Pages load but have **no styling** and buttons do nothing | Frontend not built | Run `npm run build` |
| `No application encryption key has been specified` | `.env` has no key | `php artisan key:generate` |
| No admin account after seeding | `ADMIN_PASSWORD` was empty | Set it in `.env`, then `php artisan migrate:fresh --seed` |
| `Address already in use` | Port 8000 is taken | `php artisan serve --port=8001` |
| PDF receipt download fails | `gd` extension is off | Enable `gd`, verify with `php -m` |
| `View cache` errors, stale pages | Old compiled templates | `php artisan view:clear` |
| Email never arrives | SMTP not configured | Expected. Local demo uses `PAYMENT_SIMULATION_ENABLED=true`, or set Gmail SMTP in `.env` |

---

## What NOT to touch

| Do not edit | Why |
|---|---|
| `vendor/` | Composer packages. Re-install instead of editing |
| `node_modules/` | npm packages. Re-install instead of editing |
| `public/build/` | Generated by `npm run build`. Editing has no effect |
| `storage/framework/views/` | Compiled templates. Run `php artisan view:clear` instead |
| `.env` in Git | Secrets. `.env` is ignored by Git — never commit it |

---

## Notes about what does and does not work on a group laptop

- **PayMongo webhook will not work on `localhost`.** PayMongo must call your
  computer from the outside, which is impossible on `localhost`. This is not a
  bug and not something you can fix by installing more software. Use
  `PAYMENT_SIMULATION_ENABLED=true` for local demos — the flow is identical
  except the payment is simulated instead of charged.
- **Email needs SMTP.** Without Gmail SMTP settings in `.env`, no email is sent.
  Nothing breaks — the reservation is still created and the receipt is still
  downloadable.
- **The `testing` schema is required for tests only.** The application itself
  never uses it.
- **Do not point two laptops at one database** during a demo unless you know what
  you are doing. Give each laptop its own database.

---

## Quick reference card — CMD (Option A)

**First time only:**

```bat
cd %USERPROFILE%
git clone https://github.com/Ruzz-1010/Private-Sports-Court-Reservation-System.git
cd Private-Sports-Court-Reservation-System
copy .env.example .env
notepad .env
php artisan key:generate
composer install
npm install
npm run build
php artisan migrate
php artisan db:seed
```

**Every day:**

```bat
cd %USERPROFILE%\Private-Sports-Court-Reservation-System
php artisan migrate
php artisan serve
```

Open <http://127.0.0.1:8000>. Press `Ctrl + C` to stop.

**Before the defense:**

```bat
php artisan test
php artisan view:clear
npm run build
```

## Quick reference card — Ubuntu (Option B)

**First time only:**

```bash
sudo service postgresql start
cd ~
git clone https://github.com/Ruzz-1010/Private-Sports-Court-Reservation-System.git
cd Private-Sports-Court-Reservation-System
cp .env.example .env
nano .env
php artisan key:generate
composer install
npm install
npm run build
php artisan migrate
php artisan db:seed
```

**Every day:**

```bash
sudo service postgresql start
cd ~/Private-Sports-Court-Reservation-System
php artisan migrate
php artisan serve
```

> In `nano`, edit the file, then press `Ctrl + O`, Enter to save and `Ctrl + X`
> to exit.
