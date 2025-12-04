# Project Details: Parts Inventory Tracker

> Fast lookup, accurate stock, and clean core-charge accounting for every part and every supplier.

## Scope
- Build an inventory system for a parts department that tracks procurement, storage, technician checkout, and returns (including core charges).
- Support multiple suppliers per part (OEM and aftermarket) with changing part numbers; preserve historical and alternate numbers so searches always find the right part.
- Anchor every variation (OEM, aftermarket, historical) under a single slug so lookups are fast and consistent.

## Highlights
- Traceable parts: anchor slug ties OEM, aftermarket, and historical numbers together.
- Core-aware accounting: capture charge and expected rebate per product/manufacturer.
- Warehouse-ready: aisle/shelf/bay locations, QR scan to act instantly, and audit-logged stock moves.

## Key Data Fields
- Part identity: current manufacturer number, old numbers, aftermarket numbers, and an anchor/slug to tie them together.
- Supplier info: vendors/manufacturers, URLs for reordering, sort/filter by seller, and preferred vendor flag.
- Pricing: purchase price, core charge, expected rebate (varies by product/manufacturer), and purchase date.
- Work orders: work order number (from external system) to link parts to vehicle/technician.
- Notes: product description, stock/handling notes, compatibility notes.
- Inventory location: aisle, shelf, bay (and optional bin).

## Core Workflows
- Purchase/receive: record supplier, price, core charge, quantity, and any old/alternate part numbers.
- Checkout to technician: assign to user/technician with work order, adjust on-hand quantity, and log timestamp.
- Returns: track standard returns and core returns separately; capture rebate amount when processed.
- Search/find: search by any part number (current, historical, aftermarket), manufacturer, or anchor/slug; surface location and availability instantly.

## QR/Scanner Feature
- Camera scan opens a detail panel showing technician assignment, location, available units, manufacturer, alternates, and reorder link.
- Allow “add to stock” and “remove from stock” directly from the scan flow with validation and audit logging so inventory stays trustworthy.

## Rules & Considerations
- Part numbers change—keep an audit trail and mark the active number. Group alternates to avoid duplicates.
- Core charges: store both charge and expected rebate; reconcile on return.
- Size likely lives in the part number; only add a size field if a supplier omits it.
- Require locations for stocked items to keep picking fast.
- Concurrency: wrap stock changes in transactions and lock inventory rows; require idempotency keys on stock-changing POSTs.
- Soft deletes & versioning: prefer soft deletes on master data; snapshot price/core at movement time.
- Search performance: fulltext over part_numbers/manufacturer and unique anchor slug per part.
- Security: sign QR payloads, enforce CSRF, and rate limit stock-changing endpoints.

## Technology Preferences
- PHP with a proper MVC structure, MySQL, HTML/JS, custom CSS plus locally installed Tailwind and Fontawesome.
- PDF output supported, possibly via LaTeX, for invoices/pick tickets/receipts.
- Server compatibility: must work on both Apache (with .htaccess) and IIS (with web.config) for URL rewriting and security headers.

## Security & Standards
- Follow PHP-FIG PSR-4 autoloading/namespacing (Composer-managed).
- CSRF protection with SameSite=strict cookies; role-based access checks on every route.
- Signed QR payloads; avoid exposing raw IDs; validate and sanitize inputs server-side.
- Rate limit stock-changing and auth endpoints; secrets via environment (.env), not git.

## Delivery Phases
- Phase 1: Foundation — PHP MVC skeleton; MySQL schema incl. audit_log and core liability fields; `.env` config; CRUD for parts (anchor/slug), part numbers, suppliers, pricing, locations; basic fulltext search.
- Phase 2: Workflows — receiving (transactions + row locks), checkout with work orders, returns/core returns with rebate fields, and location-aware stock adjustments with idempotency.
- Phase 3: QR/Scanner — signed QR payloads; camera scan for lookup; show availability/assignee/location; add/remove stock with validation and audit logging.
- Phase 4: Reporting & Alerts — stock on hand, aging inventory, open core liabilities, pending rebates, technician/work-order usage; alerts for low stock and core deadlines; optional daily snapshots.
- Phase 5: Polishing — PDF outputs (invoices/pick tickets) via HTML-to-PDF; performance hardening; UX polish with Tailwind/custom CSS; security hardening.

## Environments
- Development: local PHP server + MySQL; configure credentials, app URL, and feature toggles in `.env`. Use sample `.env.example` committed; never commit secrets.
- Staging: mirrors production schema and feature flags; seeded with anonymized data; enable full logging for QA; `.env` loaded from secure store.
- Production: hardened PHP runtime, managed MySQL with backups; minimal logging of PII; secrets injected via environment; enforce migrations before deploy.

## Authentication & Roles
- LDAP-authenticated accounts are treated as `user` role by default.
- Application database accounts are `admin` with elevated access.
