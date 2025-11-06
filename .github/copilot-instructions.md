# Copilot Instructions for Purchasing App

## Repository Overview

**Purpose**: Laravel 12 purchasing management system for POs, shipments, inventory, and customs data. Vietnamese primary, English fallback.

**Stack**: PHP 8.2+, Laravel 12, Filament 4 admin, Livewire, Vite 7, TailwindCSS 4, Pest 4 testing, Pint linting, MySQL/SQLite. ~300 PHP files.

## Critical Build/Test Instructions

### Initial Setup (First Time Only)

**ALWAYS run these commands in this exact order for first-time setup:**

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Install Composer dependencies (takes ~5 minutes)
composer install --no-interaction --prefer-dist --optimize-autoloader

# 3. Install npm dependencies (takes ~10 seconds)
npm install

# 4. Generate application key
php artisan key:generate

# 5. Build frontend assets (takes ~2 seconds)
npm run build

# 6. Create tests/Unit directory if missing (required by phpunit.xml)
mkdir -p tests/Unit
```

**Note**: The Composer install step includes private Flux UI credentials in CI (handled via secrets), but works without them in local development.

### Making Code Changes

**ALWAYS run these steps after making PHP code changes:**

```bash
# 1. Run linter (Laravel Pint) to fix code style
vendor/bin/pint

# 2. Clear Laravel caches (optional but recommended)
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Run tests (currently fails - see Known Issues)
vendor/bin/pest
# OR use composer script:
composer test
```

**ALWAYS run this after making frontend changes:**

```bash
# Rebuild frontend assets
npm run build
```

### Command Reference

| Command | Purpose | Duration | Notes |
|---------|---------|----------|-------|
| `composer install` | Install PHP dependencies | ~5 min | Run once or after composer.json changes |
| `npm install` | Install Node dependencies | ~10 sec | Run once or after package.json changes |
| `npm run build` | Build production assets | ~2 sec | Run after any CSS/JS changes |
| `vendor/bin/pint` | Fix PHP code style | ~5 sec | Run before committing PHP changes |
| `vendor/bin/pint --test` | Check code style without fixing | ~5 sec | Shows violations without modifying files |
| `vendor/bin/pest` | Run all tests | ~1 sec | Currently fails - see Known Issues |
| `composer test` | Run tests via composer | ~1 sec | Same as `vendor/bin/pest` |
| `composer dev` | Start dev server + queue + vite | N/A | Requires concurrently, runs all services |
| `php artisan key:generate` | Generate app key | Instant | Required after .env creation |

### Development Server

```bash
composer dev  # Runs: php artisan serve + queue:listen + npm run dev
# Or separately: php artisan serve, npm run dev, php artisan queue:listen --tries=1
```

## CI/CD Workflows

### Tests Workflow (.github/workflows/tests.yml)

Runs on: Push/PR to `develop` or `main` branches

**Environment**: Ubuntu, PHP 8.4, Node 22

**Steps**:
1. Checkout code
2. Setup PHP 8.4 with Composer v2
3. Setup Node 22 with npm cache
4. `npm i` - Install Node dependencies
5. Configure Flux credentials (requires secrets)
6. `composer install --no-interaction --prefer-dist --optimize-autoloader`
7. `cp .env.example .env`
8. `php artisan key:generate`
9. `npm run build`
10. `./vendor/bin/pest` - Run tests

**Critical**: Steps must run in this order. Frontend assets MUST be built before tests.

### Linter Workflow (.github/workflows/lint.yml)

Runs on: Push/PR to `develop` or `main` branches

**Environment**: Ubuntu, PHP 8.4

**Steps**:
1. Checkout code
2. Setup PHP 8.4
3. Configure Flux credentials
4. `composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist`
5. `npm install`
6. `vendor/bin/pint` - Fix code style

**Note**: Auto-commit of style fixes is commented out but available.

## Known Issues & Workarounds

1. **Tests Fail**: Pre-existing Livewire test failures due to missing seeded data. Not blocking for new changes.
2. **Cache Clear Needs DB**: Use `config:clear`, `route:clear`, `view:clear` instead (safe without MySQL).
3. **Pint Violations**: 191 existing violations. Run `vendor/bin/pint` before committing; don't fix unrelated files.
4. **Missing tests/Unit**: Run `mkdir -p tests/Unit` before tests (phpunit.xml expects it).

## Project Architecture

### Directory Structure

```
purchasing-app/
├── app/
│   ├── Console/Commands/     # Artisan commands (customs data processing)
│   ├── Enums/               # Status/type enums (OrderStatus, PaymentStatus, etc.)
│   ├── Filament/            # Filament admin panel (main UI)
│   │   ├── Clusters/        # Grouped admin sections
│   │   │   ├── CustomsData/ # Customs data management
│   │   │   ├── Project/     # Project management
│   │   │   └── Settings/    # App settings & configuration
│   │   ├── Pages/           # Custom Filament pages
│   │   ├── Resources/       # CRUD resources (POs, Shipments, etc.)
│   │   └── Tables/          # Table customizations
│   ├── Http/
│   │   ├── Controllers/     # API controllers
│   │   └── Middleware/      # Custom middleware (CheckPin)
│   ├── Jobs/                # Background jobs (customs data import)
│   ├── Livewire/            # Livewire components (customs data UI)
│   ├── Models/              # Eloquent models
│   ├── Observers/           # Model observers
│   ├── Policies/            # Authorization policies
│   ├── Services/            # Business logic (PO, Shipment, Payment services)
│   └── Traits/              # Reusable traits
├── config/                   # Laravel configuration files
├── database/
│   ├── factories/           # Model factories for testing
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── docs/user-guide/         # Vietnamese user documentation
├── helpers/                 # Helper functions (_app_helpers.php)
├── public/                  # Web root (index.php, build assets)
├── resources/
│   ├── css/                 # Stylesheets (app.css, Filament theme, PDF styles)
│   ├── js/                  # JavaScript (app.js)
│   ├── views/               # Blade templates
│   │   ├── livewire/        # Livewire component views
│   │   ├── pdf-view/        # PDF templates
│   │   └── vendor/          # Package-overridden views
│   └── lang/                # Translations (Vietnamese primary, English fallback)
├── routes/
│   ├── web.php              # Web routes
│   ├── api.php              # API routes (Sanctum-protected)
│   └── console.php          # Console routes
├── storage/                 # Logs, cache, sessions, uploads
├── tests/
│   ├── Feature/             # Feature tests (Filament authentication tests exist)
│   └── Unit/                # Unit tests (directory must exist but empty)
├── .env.example             # Environment template
├── artisan                  # Laravel CLI
├── composer.json            # PHP dependencies
├── package.json             # Node dependencies
├── phpunit.xml              # PHPUnit/Pest configuration
└── vite.config.js           # Vite build configuration
```

### Key Configuration Files

- **composer.json**: Defines PHP 8.2+ requirement, Laravel 12, Filament 4, Pest testing
- **package.json**: Vite 7, TailwindCSS 4, Laravel Vite plugin
- **phpunit.xml**: Uses SQLite in-memory DB for tests, RefreshDatabase trait
- **vite.config.js**: Builds app.js, app.css, Filament theme, PDF styles
- **.env.example**: Locale=Vietnamese, Timezone=Asia/Ho_Chi_Minh, Queue=database
- **.editorconfig**: 4-space indent, LF line endings, UTF-8

### Entry Points & Features

- **Web**: `/purchasing/*` (Filament admin) | **API**: `/api/*` (Sanctum) | **CLI**: `artisan`
- **Features**: Purchase Orders, Shipments, Inventory, Contacts, Projects, Customs Data (separate DB), RBAC via Spatie

## Testing

**Location**: `tests/Feature/` (1 test), `tests/Unit/` (empty, dir required)
**Config**: Pest 4 + Livewire, RefreshDatabase, SQLite in-memory, auto-login active user before each test
**New Tests**: Use `test()` function, auth is pre-configured in `tests/Pest.php`

## Validation Before Committing

**Checklist for PHP changes**:
1. Run `vendor/bin/pint` to fix code style
2. Run `vendor/bin/pest` to ensure tests pass (or don't regress)
3. Clear caches if modifying config/routes: `php artisan config:clear && php artisan route:clear`

**Checklist for frontend changes**:
1. Run `npm run build` to ensure assets compile
2. Check `public/build/` for generated files

**Checklist for dependency changes**:
1. Run `composer install` after composer.json changes
2. Run `npm install` after package.json changes
3. Commit lockfiles (composer.lock, package-lock.json)

## Additional Notes

- **Locale**: Vietnamese (vi) primary, English fallback, Asia/Ho_Chi_Minh timezone
- **Database**: MySQL (prod, 2 DBs: `purchasing` + `customs_data`), SQLite (test), database driver for queue/session/cache
- **Filament**: Panel ID `purchasing`, mount `/purchasing`, custom theme in `resources/css/filament/purchasing/theme.css`
- **Security**: Filament auth, Sanctum API tokens, Spatie permission/activity log
- **Performance**: Debugbar (local only), Vite builds in ~2 sec

---

## Instructions for Agents

**TRUST THESE INSTRUCTIONS**: The commands and order documented here have been validated. Only explore further if these instructions prove incomplete or incorrect for your specific task.

**BEFORE MAKING CHANGES**: 
1. Run `composer install && npm install` if this is your first time
2. Run `cp .env.example .env && php artisan key:generate` if .env doesn't exist
3. Run `npm run build` to ensure frontend is built
4. Run `vendor/bin/pint --test` to see existing violations

**AFTER MAKING CHANGES**:
1. Run `vendor/bin/pint` to fix code style
2. Run `npm run build` if you changed CSS/JS
3. Run `vendor/bin/pest` to check tests
4. Review git diff before committing

**COMMON MISTAKES TO AVOID**:
- Don't run `composer install` with `--no-scripts` unless you know what you're doing (it skips Filament upgrades)
- Don't skip `npm run build` before running tests in CI
- Don't modify unrelated files when fixing Pint violations
- Don't expect cache clearing to work without MySQL
- Don't forget to create tests/Unit directory before running tests

**TIME ESTIMATES**:
- First-time setup: ~6 minutes
- Code style fix: ~5 seconds
- Asset rebuild: ~2 seconds
- Test run: ~1 second (currently fails)
- Composer install: ~5 minutes
