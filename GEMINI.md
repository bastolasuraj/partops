# PartOps Project Context

## Current Focus: v4 (Production Codebase)
**Active Development Directory:** `./v4/` (default workspace).

The primary focus is the `v4` directory, which contains the production PHP MVC application served from `/mnt/shared/projects/partops` (also hosted at http://php.sbastola.com/partops/v4).

## v4: PartOps Inventory Management System

### Overview
PartOps v4 is a custom PHP inventory management system built with a lightweight MVC architecture, PSR-4 autoloading, and a strict separation of concerns.

### Technology Stack (v4)
*   **Language:** PHP 8+ (Strict Types)
*   **Framework:** Custom MVC (PSR-4)
*   **Database:** MySQL (with migration system)
*   **Frontend:** HTML/CSS (Tailwind/Custom), Vanilla JS
*   **Server:** Apache (.htaccess) / IIS (web.config)

### Directory Structure (v4)
*   **`app/`**: Core application logic (Controllers, Models, Views, Core).
*   **`database/`**: Migrations and seeds.
*   **`public/`**: Web root (`index.php`).
*   **`scripts/`**: CLI utilities (migrate, seed, verify).
*   **`vendor/`**: Composer dependencies.

### Key Features (v4)
*   **MVC Architecture:** Custom implementation with Router, Controller, Model.
*   **Inventory Operations:** Complete workflows for Parts Check-in, Checkout, and Returns (Supplier & Work Order).
*   **Work Orders:** Full management with parts tracking and core charge handling.
*   **Management:** CRUD for Parts, Suppliers, and Technicians.
*   **Dashboard:** Real-time stats, low stock alerts, and recent activity.
*   **Security:** Prepared statements, CSRF tokens (planned), Security headers.

### Development Workflow (v4)
```bash
cd v4
composer install
cp .env.example .env
# Edit .env
make migrate
make seed
make run
```
*   **Verification:** Run `make test` and `php scripts/verify-setup.php` before shipping changes.

### Next Steps (v4)
1.  **Testing:** Comprehensive manual and automated testing (Unit/Integration).
2.  **Security:** Implement Authentication/Authorization and CSRF protection.
3.  **Enhancements:** Add pagination, CSV/PDF exports, and improve loading states.
4.  **Refinement:** UI/UX polish and performance optimization.

---

## Legacy / Root Tools (Documentation Viewer & Prototype)
**Directory:** `./` (Root)

The root directory contains a documentation viewer and HTML prototype host.

### Features
*   **Docs Viewer:** Renders Markdown from `docs/`.
*   **Prototype Host:** Displays `prototype/` content in an iframe with note-taking capabilities.
*   **Tech:** PHP 8+, `league/commonmark`, Vanilla JS.

### Directory Structure (Root)
*   **`index.php`**: Entry point for docs/prototype viewer.
*   **`docs/`**: Project documentation (Design, Requirements, GEMINI notes).
*   **`prototype/`**: Static HTML/JS prototype (no backend).
*   **`.qodo/`**: Automation definitions (`agents`, `workflows`).

### Workflow (Root)
*   **Install Dependencies:** `composer install` (in root).
*   **Serve Locally:** `php -S localhost:8000 index.php` (or preferred server).
*   **Prototype:** Open `prototype/index.html` or use the `index.php` viewer.

---

## Agent Guidelines (from AGENTS.md)

### Ground Rules
- **Preserve User Edits:** Do not overwrite user edits; never revert unrelated changes. No destructive git commands.
- **Formatting:** Keep formatting consistent (ASCII, line length ≤100, CamelCase file names unless stack dictates otherwise).
- **Secrets:** Secrets stay out of the repo; use `.env` and commit only `.env.example`.
- **Context:** Default working dir for code/tests is `v4/`; switch to root only when editing shared docs or prototype files.

### Coding & Documentation Style
- **Code:** Prefer small, focused modules; explicit imports; meaningful names. Follow PSR-4 and strict typing in `v4/`.
- **Docs:** Keep sections scannable with short bullets; avoid long prose.
- **UI:** Use Flat UI CA palette (e.g., #f6e58d, #ffbe76, #ff7979, #badc58, #dff9fb, #f9ca24).

### Testing & QA
- **Backend:** Include unit tests under `tests/` if adding logic. Mirror command verbs (`make test`).
- **Prototype:** Validate layout on desktop and mobile widths; keep tabs (Docs/Prototype) functional.

### PR & Commits
- **Conventions:** Use action-oriented messages with Conventional Commits prefixes (`feat:`, `fix:`, `chore:`, `docs:`, `test:`).
- **Content:** Keep PRs small; include screenshots for UI changes and note setup steps.

### Security & Access
- **Secrets:** No secrets in git; `.env.example` only.
- **Roles:** LDAP-authenticated users = `user`, local DB accounts = `admin`.
