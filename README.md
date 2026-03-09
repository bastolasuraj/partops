# PAM (Parts Asset Management) - Project Guide

PAM is an inventory and transaction platform for parts operations. It tracks incoming purchases, outgoing checkouts, returns, stock levels, low-stock alerts, work orders, vendor returns, and cost history.

- Frontend: Vue 3 + Vite
- Backend: PHP 8 + Custom Router
- Database: MySQL / MariaDB
- Auth: LDAP + Session

## End User Guide

### What You Can Do

- Search and manage parts, suppliers, units, technicians, and work orders.
- Receive stock with your own unit price in `Incoming`.
- Issue stock from `Outgoing` to work orders/units/technicians.
- Return issued stock in `Returns`.
- Track all activity in `Transactions`.
- Handle returns to vendors in `Vendor Returns`.

### How Costing Works

> Incoming keeps the unit price you enter. Existing stock is not retroactively repriced.

> Outgoing uses FIFO costing. If one checkout consumes multiple cost layers, the transaction displays those layers as separate lines (for example: `15 @ $10` and `5 @ $20`).

### Daily Workflow

1. Open `https://localhost:5173/` and sign in.
2. Use `Incoming` to add purchased items with current unit price.
3. Use `Outgoing` to issue items to a reference (work order, unit, or technician).
4. Use `Returns` when items come back to shelf.
5. Audit activity in `Transactions` and review low stock in `Low Stock`.

## Developer Guide

### Project Structure

| Path | Purpose |
| --- | --- |
| `frontend/` | Vue app source and build output (`dist/`). |
| `backend/public/` | API entrypoint and deployed frontend static assets. |
| `backend/app/Controllers/` | HTTP route handlers for each domain area. |
| `backend/app/Models/` | DB access and business logic (including FIFO costing). |
| `backend/database/` | Schema and migration scripts. |
| `docs and tests/` | Support scripts and legacy docs/testing utilities. |

### Local Prerequisites

- PHP 8.x with `pdo_mysql` (and LDAP extension if LDAP auth is used).
- Node.js 18+ and npm.
- MySQL/MariaDB reachable by backend config.

### Configuration

- Backend DB config: `backend/config/database.php` (or env vars in `backend/.env`).
- LDAP config: `backend/config/ldap.php`.
- Frontend API proxy target: `frontend/vite.config.js` (`VITE_API_PROXY_TARGET`).

## Run Locally

### 1) Initialize Database

```bash
mysql -u <user> -p < backend/database/schema.sql
```

### 2) Start Backend API (port 2345)

```bash
cd backend
php -S 0.0.0.0:2345 -t public public/index.php
```

### 3) Start Frontend Dev Server (HTTPS on 5173)

```bash
cd frontend
npm install
npm run dev -- --host :: --port 5173
```

Open the app at `https://localhost:5173/`. API calls are proxied to `http://localhost:2345`.

## Build and Replace Frontend in Backend Public

### Backup current public folder

```powershell
cd backend
Compress-Archive -Path public -DestinationPath public_backup_YYYYMMDD_HHMMSS.zip
```

### Build frontend

```bash
cd frontend
npm run build
```

### Replace deployed static files

```text
Copy frontend/dist/index.html -> backend/public/index.html
Copy frontend/dist/assets/*   -> backend/public/assets/*
```

Keep backend runtime files in place: `backend/public/index.php`, `.htaccess`, and `web.config`.

## Authentication Notes

- Session-based authentication with LDAP login path enabled.
- Frontend guards protected routes and redirects to `/login` on 401/session expiry.
- Review database seed users in `backend/database/schema.sql` before production use.

In current backend logic, local login is blocked when LDAP allowed-groups are configured (the default LDAP config includes groups). Use LDAP accounts/groups for standard login.

## Complete API Endpoints

All routes below are exposed from the backend router in `backend/public/index.php`.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api` | API info and endpoint map. |
| `POST` | `/api/auth/login` | Login via local/LDAP. |
| `POST` | `/api/auth/logout` | Logout and destroy session. |
| `GET` | `/api/auth/me` | Current authenticated user. |
| `GET` | `/api/auth/check` | Auth/session status check. |
| `GET` | `/api/suppliers` | List suppliers. |
| `GET` | `/api/suppliers/{id}` | Get supplier by ID. |
| `POST` | `/api/suppliers` | Create supplier. |
| `PUT` | `/api/suppliers/{id}` | Update supplier. |
| `DELETE` | `/api/suppliers/{id}` | Delete supplier. |
| `GET` | `/api/technicians` | List technicians. |
| `GET` | `/api/technicians/{id}` | Get technician by ID. |
| `POST` | `/api/technicians` | Create technician. |
| `PUT` | `/api/technicians/{id}` | Update technician. |
| `DELETE` | `/api/technicians/{id}` | Delete technician. |
| `GET` | `/api/units` | List units. |
| `GET` | `/api/units/{id}` | Get unit by ID. |
| `POST` | `/api/units` | Create unit. |
| `PUT` | `/api/units/{id}` | Update unit. |
| `DELETE` | `/api/units/{id}` | Delete unit. |
| `GET` | `/api/parts` | List/search parts. |
| `GET` | `/api/parts/low-stock` | Get low stock parts. |
| `GET` | `/api/parts/check-duplicate` | Check duplicate supplier PN. |
| `GET` | `/api/parts/fowler-mapping` | Get Fowler supplier mapping. |
| `POST` | `/api/parts/bulk-import` | Bulk import parts. |
| `GET` | `/api/parts/{id}` | Get part by ID. |
| `GET` | `/api/parts/{id}/stock` | Get stock for one part. |
| `POST` | `/api/parts` | Create part. |
| `PUT` | `/api/parts/{id}` | Update part. |
| `DELETE` | `/api/parts/{id}` | Delete part (soft delete rules apply). |
| `GET` | `/api/work-orders` | List work orders. |
| `GET` | `/api/work-orders/open` | List open work orders. |
| `GET` | `/api/work-orders/{id}` | Get work order by ID. |
| `POST` | `/api/work-orders` | Create work order. |
| `PUT` | `/api/work-orders/{id}` | Update work order. |
| `DELETE` | `/api/work-orders/{id}` | Delete work order. |
| `GET` | `/api/inventory/stock-levels` | Get stock levels. |
| `GET` | `/api/inventory/transactions` | Get transaction history. |
| `GET` | `/api/inventory/returnable-items` | Get returnable items for a reference. |
| `POST` | `/api/inventory/incoming` | Process incoming inventory. |
| `POST` | `/api/inventory/checkout` | Process outgoing checkout (FIFO layer costing). |
| `POST` | `/api/inventory/return` | Process return to inventory. |
| `GET` | `/api/vendor-returns` | List vendor returns. |
| `GET` | `/api/vendor-returns/{id}` | Get vendor return by ID. |
| `POST` | `/api/vendor-returns` | Create vendor return. |
| `PUT` | `/api/vendor-returns/{id}` | Update vendor return. |
| `DELETE` | `/api/vendor-returns/{id}` | Delete vendor return. |
| `GET` | `/api/settings` | Get app settings. |
| `PUT` | `/api/settings` | Update app settings. |
| `GET` | `/api/reports/summary` | Get consolidated report dataset (users, suppliers, transactions, parts). |
| `GET` | `/api/reports/archive` | List archived report copies saved on the server. |
| `GET` | `/api/reports/archive/download` | Download one archived report copy by path. |
| `POST` | `/api/reports/archive` | Archive a downloaded report file copy (PDF/XLSX) on the server. |

Non-API route: `GET /readme.html` serves this documentation page from the backend host.

## Troubleshooting

- If `https://localhost:5173` does not load, confirm frontend dev server is running and listening on port 5173.
- If login fails, inspect `backend/storage/logs/error.log` and verify LDAP + DB connectivity.
- If API calls fail, verify backend is reachable at `http://localhost:2345`.
- If transaction costs look unexpected, check FIFO layers in `Transactions` expanded details.

---

Document source: `v1/readme.html`
