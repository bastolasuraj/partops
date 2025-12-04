# Project Design

> Crisp map of the code layout, data model, and environment wiring.

## Directory Layout (proposed)
- `public/` — web root; front controller (`index.php`), assets, compiled Tailwind CSS.
- `src/` — application code: `Controllers/`, `Models/`, `Views/`, `Services/`, `Config/`.
- `database/` — migrations and seeds.
- `resources/` ��� Tailwind input CSS, JS modules, images/icons (local Fontawesome).
- `scripts/` — helper scripts (schema export, lint/build).
- `.env` — environment values (DB, APP_URL, feature flags); `.env.example` checked in.
- `.htaccess` — Apache URL rewriting and security headers (for Apache deployments).
- `web.config` — IIS URL rewriting and security headers (for IIS deployments).
- `tests/` — unit/integration tests.
- `storage/` — cache/logs/tmp uploads (kept outside `public/`).

### Project Tree (illustrative)
```
project-root/
├─ public/
│  └─ index.php
├─ src/
│  ├─ Controllers/
│  ├─ Models/
│  ├─ Views/
│  ├─ Services/
│  └─ Config/
├─ database/
│  ├─ migrations/
│  └─ seeds/
├─ resources/
│  ├─ css/
│  ├─ js/
│  └─ icons/
├─ scripts/
├─ tests/
├─ storage/
├─ .env.example
└─ composer.json
```

## Database Design (MySQL)
- `parts` — id, anchor_slug (UNIQUE), name/description, active_part_number_id, notes, is_active BOOL, created_at/updated_at.
- `part_numbers` — id, part_id (FK), value, type ENUM('active','historical','aftermarket'), manufacturer, is_active BOOL, is_primary BOOL DEFAULT 0, created_at.
- `suppliers` — id, name, contact info, reorder_url, is_preferred BOOL, is_active BOOL.
- `part_suppliers` — id, part_id, supplier_id, sku/number, price, currency, core_charge, expected_rebate, recorded_at.
- `locations` — id, aisle, shelf, bay, bin, is_active BOOL.
- `inventory_levels` — id, part_id, location_id, on_hand INT UNSIGNED, reserved INT UNSIGNED, UNIQUE(part_id, location_id).
- `inventory_moves` — id, part_id, location_id, qty INT, direction ENUM('in','out'), reason ENUM('receive','checkout','return','core_return','adjust'), work_order_id, technician_id, supplier_id, price_at_tx DECIMAL(10,2), currency CHAR(3), core_charge_at_tx DECIMAL(10,2), core_rebate_expected DECIMAL(10,2), core_rebate_received DECIMAL(10,2), core_due_state ENUM('none','due','sent','rebated'), notes, created_at.
- `core_liabilities` (optional) — id, receive_move_id FK, part_id, qty_due, supplier_id, core_charge, expected_rebate, due_by, sent_at, rebate_received_at, rebate_amount, status.
- `work_orders` — id, external_ref (UNIQUE), vehicle_ref, status, opened_at, closed_at.
- `technicians` — id, name, email/phone, is_active BOOL.
- `qr_codes` — id, part_id, payload (signed token/URL), issued_at, expires_at (nullable).
- `users` — id, username, email, auth_source ENUM('ldap','local'), password_hash (nullable for LDAP), role ENUM('user','admin'), is_active BOOL, created_at, last_login_at.
- `audit_log` — id, user_id, action, entity_type, entity_id, diff JSON, created_at, ip.

Indexes & search:
- Fulltext over (part_numbers.value, manufacturer) and index on parts.anchor_slug.

## Key Connections
- `part_numbers.part_id` → `parts.id` (one part, many numbers; active stored on parts).
- `part_suppliers.part_id`/`supplier_id` link pricing and core terms per vendor.
- `inventory_levels` ties a part to a location; `inventory_moves` is the audit trail and adjusts levels.
- `inventory_moves.work_order_id` and `technician_id` capture checkout/return context.
- `qr_codes.part_id` enables QR lookup; payload resolves to part detail.
- Enforce cascading deletes cautiously (prefer soft delete flags) to preserve audit history.
- `users.role` defines access: LDAP-authenticated users default to `user`; locally stored accounts are `admin`.

## Environment & Connections
- Configure DB and app settings via `.env`: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`, `APP_ENV`, `LOG_CHANNEL`.
- Use a DB connection pool/manager initialized in bootstrap; share via DI container.
- Separate write/read queries if replicas are added later; keep transactions around inventory movements and core return updates; lock inventory_levels rows during stock changes.
- Require idempotency keys on stock-changing POSTs; log structured JSON with correlation IDs.
- Add LDAP settings to `.env` for user auth: `LDAP_HOST`, `LDAP_PORT`, `LDAP_BASE_DN`, `LDAP_BIND_DN`, `LDAP_BIND_PASS`; default role mapping assigns `user` to LDAP logins and `admin` to local DB accounts.
- Security: CSRF protection on forms, signed QR tokens, and rate limiting on stock-changing endpoints.

## Security & Standards
- PSR-4 autoloading/namespacing via Composer; meaningful namespaces for Controllers/Models/Services.
- CSRF tokens and SameSite=strict session cookies; role-based middleware for route access.
- Signed QR payloads; input validation/sanitization on all endpoints; avoid exposing raw IDs.
- Rate limit stock-changing and authentication routes; secrets via environment, never in git.

## UX Notes
- Keep navigation shallow and predictable: Parts, Receiving, Checkout, Returns/Core, Locations, Reports, Admin.
- Make QR scan the fastest path: one tap to scan, instant detail pane with add/remove stock actions.
- Use Tailwind with a limited palette (brand primary + neutral) to keep the UI crisp and readable in low light warehouse settings.
