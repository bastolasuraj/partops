# Prototype Updates Needed

> Align the static prototype with security, concurrency, and core liability enhancements.

## Overview
The prototype currently demonstrates basic workflows. Based on the updated requirements (PSR-4, CSRF, signed QR, idempotency, core liability tracking, soft deletes), the following changes are recommended to keep the prototype aligned with the production design.

## Recommended Changes

### 1. Security & Standards Indicators

**QR/Scanner View**
- Add visual indicator that QR codes are signed/tokenized (not raw IDs).
- Show placeholder for "Token: abc123xyz..." instead of exposing part IDs directly.
- Add note: "QR payloads are cryptographically signed for security."

**Forms (Receiving, Checkout, Returns)**
- Add hidden CSRF token field placeholder: `<input type="hidden" name="csrf_token" value="[auto-generated]" />`.
- Add visual note: "CSRF protection enabled" near submit buttons.

**Admin View**
- Add note about PSR-4 autoloading: "Code follows PSR-4 namespacing standards."
- Show rate limiting status: "Rate limits: 10 req/min on stock changes."

### 2. Concurrency & Idempotency

**Receiving, Checkout, Returns Forms**
- Add idempotency key field (read-only, auto-generated):
  ```html
  <label>Idempotency Key (auto)<input value="idem-20240611-abc123" readonly /></label>
  ```
- Add note: "Prevents duplicate submissions if network retries occur."

**Part Detail / Inventory Levels**
- Add "Reserved" column to show locked quantities during transactions.
- Show note: "Stock changes use row-level locks to prevent race conditions."

### 3. Core Liability Tracking

**Returns & Core View**
- Expand form to include core due state:
  ```html
  <label>Core Due State
    <select>
      <option>None</option>
      <option>Due</option>
      <option>Sent</option>
      <option>Rebated</option>
    </select>
  </label>
  <label>Rebate Expected<input type="number" placeholder="0.00" /></label>
  <label>Rebate Received<input type="number" placeholder="0.00" /></label>
  <label>Due By Date<input type="date" /></label>
  ```

**Reports View**
- Add card: "Core Liabilities Due" with count and total value.
- Add card: "Overdue Core Returns" with aging buckets (30d, 60d, 90d+).

**Audit Log View**
- Add columns for core tracking:
  - Core Charge @ Tx
  - Core Rebate Expected
  - Core Rebate Received
  - Core Due State

### 4. Soft Deletes & Active Flags

**Parts, Suppliers, Locations, Technicians Tables**
- Add "Active" column with Yes/No badges.
- Add filter dropdown: "All / Active Only / Inactive Only."
- Show note: "Soft deletes preserve audit history; inactive items hidden by default."

**Part Detail View**
- Add "Active" toggle for the part itself.
- Show "Deactivated on [date]" if inactive.

### 5. Search & Indexing Hints

**Parts Catalog View**
- Update search placeholder: "Fulltext search: anchor, part #, manufacturer…"
- Add note below search: "Indexed for fast lookup across OEM/aftermarket/historical numbers."

**Suppliers, Technicians, Work Orders Views**
- Add similar search hints indicating indexed fields.

### 6. Movement-Time Snapshots

**Audit Log View**
- Add columns:
  - Price @ Tx
  - Currency
  - Core Charge @ Tx
- Add note: "Price/core values captured at movement time for accurate historical reporting."

**Receiving Form**
- Add note: "Price and core charge will be snapshotted in the audit trail."

### 7. Additional Views/Features

**New View: Core Liabilities Dashboard**
- Add button in sidebar: "Core Liabilities."
- Show table:
  - Part Anchor
  - Qty Due
  - Supplier
  - Core Charge
  - Expected Rebate
  - Due By
  - Days Overdue
  - Status (Due / Sent / Rebated)
- Add actions: "Mark as Sent," "Record Rebate."

**Audit Log Enhancements**
- Add filter for "Core-related only."
- Add export button: "Export Audit CSV (with correlation IDs)."

### 8. UX/Visual Enhancements

**Color Coding**
- Use existing palette:
  - Red (`rgb(188,59,40)`) for overdue cores, low stock alerts.
  - Yellow (`rgb(255,222,63)`) for warnings (e.g., core due soon).
  - Dark (`rgb(35,31,32)`) for base text.

**Responsive Validation**
- Add inline validation hints on forms (e.g., "Location required for stocked items").
- Show "Validating…" spinner on submit buttons.

**Mobile/Tablet Optimization**
- Ensure QR scanner view is touch-friendly.
- Stack form fields vertically on narrow screens.

## Implementation Priority

1. **High Priority** (align with Phase 1-2 requirements):
   - CSRF token placeholders on all forms.
   - Idempotency key fields on stock-changing forms.
   - Core due state fields in Returns view.
   - Soft delete "Active" columns and filters.

2. **Medium Priority** (align with Phase 3-4):
   - Signed QR token indicator in QR/Scanner view.
   - Core Liabilities dashboard view.
   - Movement-time snapshot columns in Audit Log.
   - Search/indexing hints.

3. **Low Priority** (polish for Phase 5):
   - Rate limiting status indicators.
   - PSR-4 note in Admin view.
   - Responsive/mobile optimizations.
   - Color-coded alerts and warnings.

## Notes
- The prototype remains static HTML/CSS/JS; these changes are visual/structural only.
- Backend implementation will enforce the actual security, concurrency, and audit logic.
- Keep the prototype scannable and easy to demo; avoid clutter.
