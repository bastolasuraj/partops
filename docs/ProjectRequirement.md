# Project Requirements: Parts Inventory Tracker

> What we ship, in what order, and the rails to keep it safe in dev/stage/prod.

## Overview
- Build a PHP MVC + MySQL inventory system for parts departments: procurement, storage, technician checkout, returns (including core charges), and QR-based lookups.
- Handle changing/alternate part numbers (OEM/aftermarket), supplier differentiation, price/core tracking, and precise locations (aisle/shelf/bay).

## Quick Highlights
- Single source of truth for `parts`, `suppliers`, `locations`, and `work orders`, with anchor slugs to tie OEM + aftermarket numbers together.
- Inventory you can trust: audit every movement, core charge/rebate, and checkout/return.
- Fast lookup via QR scan; easy reorder via stored supplier URLs.

## Delivery Phases
- **Phase 1: Foundation** — PHP MVC skeleton; MySQL schema for parts, suppliers, locations, core charges, part-number history/alternates, work orders, audit tables, and users; `.env` config; basic CRUD and fulltext search; include audit_log and core liability fields.
- **Phase 2: Workflows** — receiving/purchasing with transactions + row locks; price + core capture; checkout to technician with work order linkage; returns and core returns with rebate logging; stock/location adjustments with idempotency keys.
- **Phase 3: QR/Scanner** — signed QR payloads; camera scan to open part detail (availability, location, technician assignment, alternates); add/remove stock with validation and audit trail.
- **Phase 4: Reporting & Alerts** — stock on hand, aging inventory, open core liabilities, pending rebates, technician/work-order usage; alerts for low stock and core deadlines; optional daily inventory snapshots.
- **Phase 5: Polishing** — PDF outputs (invoices/pick tickets/receipts), performance and UX polish with Tailwind/custom CSS/Fontawesome, security hardening and backups.

## Environments & Config (.env)
- Development: local PHP server + MySQL; use `.env` for DB credentials, app URL, feature flags; commit `.env.example` only. Enable verbose logging; PSR-4 via Composer autoload.
- Staging: mirrors production schema and feature flags; seed with anonymized data; full request logging for QA; `.env` pulled from secure store; verify CSRF, idempotency, and rate limits.
- Production: hardened PHP runtime; managed MySQL with backups; secrets via environment; minimal PII logging; require migrations before deploy; enforce SameSite=strict cookies and signed QR token validation.
- Server compatibility: ensure deployment works on both Apache (with .htaccess for rewrites/headers) and IIS (with web.config for URL rewriting and security headers).

## Security & Standards
- Follow PHP-FIG PSR-4 autoloading/namespacing; use Composer autoload.
- Enforce CSRF tokens and SameSite=strict cookies; role-based access middleware.
- Signed QR payloads; server-side input validation and sanitization.
- Rate limit stock-changing and authentication endpoints; no secrets in git.

## Core Feature Requirements
- Part records: active part number, historical/old numbers, aftermarket numbers, anchor/slug to group equivalents; manufacturer/vendor link and reorder URL; soft delete flags on master data.
- Pricing: purchase price history, core charge, expected rebate by product/manufacturer, currency, and dates; snapshot price/core at movement time.
- Locations: aisle, shelf, bay (and optional bin); required for stocked items; unique(part, location) on inventory levels.
- Work orders/technicians: link parts to work orders and assigned technician; checkout status and timestamps.
- Notes: product description, stock/handling notes, compatibility comments.
- Returns & adjustments: standard returns and core returns with rebate capture; audit every adjustment via inventory_moves; wrap stock changes in transactions and row locks; idempotency keys on POSTs.
- Search/indexing: fulltext over part numbers/manufacturer plus unique anchor slug per part for fast lookup.
- Security: signed QR tokens, CSRF protection, and rate limiting on stock-changing endpoints.
- Auth: LDAP-authenticated users are assigned `user` role; database-stored accounts are `admin` with elevated access.

## Development & Deployment Steps
- Dev: install PHP deps, run MySQL migrations, start local server, seed sample data; keep scripts in `Makefile`/tasks (`make setup`, `make migrate`, `make test`, `make run`); verify LDAP config via `.env`; enable structured JSON logs; adhere to PSR-4 namespaces and autoload.
- Staging: deploy from main; apply migrations; load anonymized fixtures; enable QA flags; verify QR scanning, idempotency handling, CSRF, rate limits, and role-based access on target devices.
- Prod: deploy tagged releases; run migrations with backups; rotate secrets via environment; monitor logs/metrics; enforce CSRF/rate limits; verify signed QR validation, PDFs, scanning flows, and LDAP vs admin access post-deploy.
