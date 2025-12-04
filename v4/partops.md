# Parts & Inventory System – AI-Friendly Specification

This document describes the database schema and forms (in sequence) for a parts / inventory system. It is written so that an AI code generator can implement it in any stack (e.g., PHP + MySQL, Laravel, Django, etc.).

---

## 1. Database Design

### 1.1. `suppliers` Table

Stores parts suppliers/manufacturers.

```text
Table: suppliers
- id                (PK, int, auto increment)
- name              (varchar, required)
- phone             (varchar, nullable)
- email             (varchar, nullable)
- address           (text, nullable)
- url               (varchar, nullable)
- created_at        (datetime)
- updated_at        (datetime)
```

---

### 1.2. `technicians` Table

People checking parts in/out.

```text
Table: technicians
- id                (PK, int, auto increment)
- name              (varchar, required)
- phone             (varchar, required)
- email             (varchar, nullable)
- created_at        (datetime)
- updated_at        (datetime)
```

---

### 1.3. `parts` Table

Master list of all parts.

```text
Table: parts
- id                    (PK, int, auto increment)
- fowler_part_number    (varchar, unique, required)
- name                  (varchar, required)
- supplier_id           (FK → suppliers.id, nullable)
- supplier_part_number  (varchar, nullable)
- location_aisle        (varchar, nullable)
- location_shelf        (varchar, nullable)
- location_bay          (varchar, nullable)
- low_stock_threshold   (int, nullable)
- notes                 (text, nullable)
- created_at            (datetime)
- updated_at            (datetime)
```

> When a supplier + supplier_part_number exists, the UI should be able to show all related supplier/manufacturer numbers for that Fowler part.

---

### 1.4. `work_orders` Table

Represents a work order a tech is working on.

```text
Table: work_orders
- id                (PK, int, auto increment)
- wo_number         (varchar, unique, required)
- technician_id     (FK → technicians.id, nullable)
- unit_number       (varchar, nullable)
- created_at        (datetime)
- updated_at        (datetime)
```

> Used for parts checkout and work order returns. Technician + unit can be autofilled from the work order.

---

### 1.5. `part_checkins` Table (4a – New Parts Check-In)

```text
Table: part_checkins
- id                    (PK, int, auto increment)
- part_id               (FK → parts.id, required)
- supplier_id           (FK → suppliers.id, nullable)
- supplier_part_number  (varchar, nullable)
- price                 (decimal(10,2), required)
- has_core_charge       (boolean, required, default false)
- expected_rebate       (decimal(10,2), nullable)
- quantity              (int, required)
- notes                 (text, nullable)
- created_at            (datetime)
```

---

### 1.6. `part_checkouts` Table (5 – Parts Checkout to Work Order / Technician)

```text
Table: part_checkouts
- id                    (PK, int, auto increment)
- work_order_id         (FK → work_orders.id, required)
- technician_id         (FK → technicians.id, required)  -- autofill from work order
- unit_number           (varchar, required)              -- autofill from work order
- part_id               (FK → parts.id, required)
- supplier_part_number  (varchar, nullable)              -- optional, can be autofilled
- quantity              (int, required)
- created_at            (datetime)
```

---

### 1.7. `work_order_returns` Table (4b – Work Order Return)

```text
Table: work_order_returns
- id                    (PK, int, auto increment)
- work_order_id         (FK → work_orders.id, required)
- part_id               (FK → parts.id, required)
- return_quantity       (int, required)   -- must be <= total taken out for this WO+part
- required_quantity     (int, nullable)   -- “req. qty” from sketch
- created_at            (datetime)
```

> UI logic should validate `return_quantity <= total_checked_out_for_that_WO_and_part`.

---

### 1.8. `returns` Table (6a + 6b – Supplier Returns + Core Charge Returns)

```text
Table: returns
- id                    (PK, int, auto increment)
- supplier_id           (FK → suppliers.id, required)
- supplier_part_number  (varchar, required)
- part_id               (FK → parts.id, nullable)   -- Fowler part number link
- quantity              (int, required)
- notes                 (text, nullable)

-- Core charge-specific fields (6b)
- is_core_charge        (boolean, required, default false)
- core_charge_amount    (decimal(10,2), nullable)
- expected_rebate       (decimal(10,2), nullable)
- rebate_received       (decimal(10,2), nullable)

- created_at            (datetime)
```

> For a **standard return**, `is_core_charge = false` and core fields can be null.  
> For a **core charge return**, `is_core_charge = true` and core fields should be filled.

---

## 2. Forms – In Sequence

### 2.1. Form 1 – Parts Entry

**Purpose:** Create and maintain master part records.

**Backed by:** `parts` table (+ lookups from `suppliers`).

**Fields:**
1. `fowler_part_number` – text, required, unique.
2. `name` – text, required.
3. `supplier_id` – dropdown, optional, list from `suppliers`.
4. `supplier_part_number` – text, optional.
5. `location_aisle` – text, optional.
6. `location_shelf` – text, optional.
7. `location_bay` – text, optional.
8. `low_stock_threshold` – integer, optional.
9. `notes` – textarea, optional.

**Behaviour / AI Hints:**
- When user selects a `supplier_id`, show existing `supplier_part_number` values related to this Fowler part (if any).
- When a part is selected elsewhere (checkout, returns, etc.), this form’s data should drive name + location autofill.

---

### 2.2. Form 2 – Suppliers (Form + Table in Same View)

**Purpose:** Manage suppliers.

**Backed by:** `suppliers` table.

**Form Fields:**
1. `name` – text, required.
2. `phone` – text, optional.
3. `email` – text, optional.
4. `address` – textarea, optional.
5. `url` – text, optional.

**Table View Behaviour:**
- Show columns: `name`, `phone`, `email`, `url`.
- `email` column should render as `mailto:` link.
- `phone` column should render as `tel:` link.
- `name` should be a hyperlink to `url` if `url` is present.
- Support inline **add/edit** in the same view (no separate “edit page” needed).

---

### 2.3. Form 3 – Technicians (Form + Table)

**Purpose:** Manage technicians.

**Backed by:** `technicians` table.

**Form Fields:**
1. `name` – text, required.
2. `phone` – text, required.
3. `email` – text, optional.

**Table Behaviour:**
- Columns: `name`, `phone`, `email`, “Call” button.
- `phone` as `tel:` link.
- `email` as `mailto:` link.
- “Call” button should trigger a call via `tel:` on supported devices.
- Same inline add/edit/delete behaviour as `suppliers`.

---

### 2.4. Form 4a – Parts Check-In (New Parts)

**Purpose:** Record incoming stock (new parts).

**Backed by:** `part_checkins` table, linked to `parts` and `suppliers`.

**Fields:**
1. `part_id` – dropdown/searchable; label = `fowler_part_number + name`. Required.
2. `supplier_id` – dropdown; required if known.
3. `supplier_part_number` – text; optional, but should default or suggest known supplier part for (`part_id`,`supplier_id`).
4. `price` – decimal, required.
5. `has_core_charge` – yes/no flag.
6. `expected_rebate` – decimal, optional; only visible when `has_core_charge = yes`.
7. `quantity` – integer, required.
8. `notes` – textarea, optional.

**Behaviour:**
- When `part_id` is selected, the UI can auto-show existing mappings of supplier + supplier_part_number.
- Saving a check-in should **increase on-hand stock** for that `part_id` (if you implement inventory balances later).

---

### 2.5. Form 4b – Work Order Return

**Purpose:** Return unused parts from a work order back into stock.

**Backed by:** `work_order_returns` table, plus data from `work_orders` and `part_checkouts`.

**Fields:**
1. `work_order_id` – dropdown/search by `wo_number`. Required.
   - On select, **autofill**:
     - `unit_number` from `work_orders.unit_number`
     - `technician_id` from `work_orders.technician_id` (display tech name).
2. Parts section (repeatable rows):
   - `part_id` – dropdown listing all parts that were previously checked out on this work order (from `part_checkouts`).
   - `return_quantity` – integer, required.  
     - Validation: `return_quantity` must be **≤ total quantity checked out for that work order + part**.
   - `required_quantity` – integer, optional (your “req. qty” box).

**Behaviour:**
- When `work_order_id` is selected, the parts list should auto-populate with all parts taken on that WO.
- User can adjust `return_quantity` and `required_quantity` row by row.
- Saving should:
  - Increase on-hand stock by `return_quantity`.
  - Optionally update some “remaining on WO” metrics if desired.

---

### 2.6. Form 5 – Parts Checkout

**Purpose:** Issue parts to a technician / work order.

**Backed by:** `part_checkouts` table, plus `work_orders`, `technicians`, `parts`.

**Header Section:**
1. `work_order_id` – dropdown/search by `wo_number`. Required.
   - On select:
     - Autofill `technician_id` and show tech name.
     - Autofill `unit_number`.
2. Display fields (read-only or editable as needed):
   - `technician_name` (from `technician_id`).
   - `unit_number`.

**Parts Section (repeatable rows):**
1. `part_id` – dropdown/search. Required.
   - On select, autofill:
     - `name` (part name, read-only display).
     - `supplier_part_number` if there is a default.
2. `manufacturer_part_number` / `supplier_part_number` – optional input; can override or set.
3. `quantity` – integer, required.

**Behaviour:**
- Allow dynamic add/remove rows (“repeat n times”).
- Saving should **decrease on-hand stock** by `quantity` per row.
- These checkout records are what 4b uses to determine “what was taken” for a given work order.

---

### 2.7. Form 6a – Returns (Standard Supplier Return)

**Purpose:** Return parts to supplier (non-core-charge).

**Backed by:** `returns` table.

**Fields:**
1. `supplier_id` – dropdown, required.
2. `supplier_part_number` – text, required.
3. `part_id` – dropdown, optional; when selected, autofill `fowler_part_number` and name for display.
4. `quantity` – integer, required.
5. `notes` – textarea, optional.

**Behaviour:**
- `is_core_charge` should be set to `false` in this flow.
- Saving should **decrease on-hand stock** of the related `part_id` if it’s linked.

---

### 2.8. Form 6b – Core Charge Return (Extension of 6a)

**Purpose:** Return core charges and track rebates.

**Backed by:** same `returns` table, with core fields populated.

**Base Fields:** same as 6a (supplier, supplier_part_number, part, quantity, notes).

**Extra Fields:**
1. `core_charge_amount` – decimal, required.
2. `expected_rebate` – decimal, required.
3. `rebate_received` – decimal, optional (fill when money actually comes in).

**Behaviour:**
- `is_core_charge` should be **true** for this form.
- This is conceptually “6a + extra fields”; UI can be a toggle **“This is a core charge return”** that reveals the three extra money fields.
- `rebate_received` may be updated later (edit form) when the rebate shows up.
