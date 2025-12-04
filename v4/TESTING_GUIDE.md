# PartOps v4 - Testing Guide

Quick reference for testing the completed views.

---

## 🚀 QUICK START

```bash
cd /mnt/shared/projects/partops/v4

# Install dependencies (if not done)
composer install

# Setup environment
cp .env.example .env
# Edit .env with database credentials

# Run migrations
php scripts/migrate.php

# Seed sample data
php scripts/seed.php

# Start server
php -S localhost:8000 -t public

# Or use Makefile
make setup
make migrate
make seed
make run
```

Visit: http://localhost:8000

---

## 📋 VIEW TESTING CHECKLIST

### Home & Dashboard
- [ ] **/** - Landing page loads with feature cards
- [ ] **/dashboard** - Stats display, low stock alerts, recent activity

### Parts Module
- [ ] **/parts** - List displays with search and filters
- [ ] **/parts?q=test** - Search works
- [ ] **/parts?supplier_id=1** - Supplier filter works
- [ ] **/parts?low=1** - Low stock filter works
- [ ] **/parts/create** - Form displays all fields
- [ ] Submit create form - Part is created
- [ ] **/parts/1** - Detail page shows part info and QR code
- [ ] **/parts/1/edit** - Edit form pre-populated
- [ ] Submit edit form - Part is updated
- [ ] Delete part - Confirmation and deletion works

### Suppliers Module
- [ ] **/suppliers** - List displays with inline add form
- [ ] Submit add form - Supplier is created
- [ ] Phone links work (tel:)
- [ ] Email links work (mailto:)
- [ ] Website links open in new tab
- [ ] Edit button works
- [ ] Delete with confirmation works

### Technicians Module
- [ ] **/technicians** - List displays with inline add form
- [ ] Submit add form - Technician is created
- [ ] Call button displays with icon
- [ ] Phone links work (tel:)
- [ ] Email links work (mailto:)
- [ ] Edit button works
- [ ] Delete with confirmation works

### Work Orders Module
- [ ] **/work-orders** - List displays with filters
- [ ] Search by WO number works
- [ ] Filter by technician works
- [ ] Filter by unit number works
- [ ] "New Work Order" button opens modal
- [ ] Submit create form - WO is created
- [ ] **/work-orders/1** - Detail page shows WO info and parts
- [ ] "Add Parts" link goes to checkout with pre-selected WO
- [ ] "Return" links work for each part

### Check-in Module
- [ ] **/checkins** - Recent check-ins list displays
- [ ] **/checkins/create** - Form displays
- [ ] Part selection updates supplier info
- [ ] Core charge checkbox toggles rebate field
- [ ] Submit form - Check-in is recorded
- [ ] Inventory increases after check-in

### Checkout Module
- [ ] **/checkouts** - Recent checkouts list displays
- [ ] **/checkouts/create** - Form displays
- [ ] WO selection auto-fills technician and unit
- [ ] Part selection shows stock level
- [ ] "Add Part" button adds new row
- [ ] "Remove" button removes row (keeps at least 1)
- [ ] Submit form - Checkout is recorded
- [ ] Inventory decreases after checkout
- [ ] Pre-selection via ?wo_id works

### Returns Module
- [ ] **/returns** - Dashboard displays with feature cards
- [ ] Outstanding cores table displays
- [ ] **/checkins/work-order** - Form displays
- [ ] WO selection loads parts via AJAX
- [ ] Part selection shows checked-out quantity
- [ ] Submit form - Return is recorded
- [ ] Inventory increases after return
- [ ] **/returns/supplier** - Form displays
- [ ] Core charge checkbox toggles fields
- [ ] Submit form - Supplier return is recorded

### Error Pages
- [ ] **/invalid-url** - 404 page displays
- [ ] Links on 404 page work

---

## 🧪 FUNCTIONAL TESTS

### Search & Filters
```
Test: Search parts by name
1. Go to /parts
2. Enter "filter" in search box
3. Click Apply
4. Verify results contain "filter"

Test: Filter by supplier
1. Go to /parts
2. Select a supplier from dropdown
3. Click Apply
4. Verify all results are from that supplier

Test: Low stock filter
1. Go to /parts
2. Select "Only Low" from Low Stock dropdown
3. Click Apply
4. Verify all results have on_hand <= low_stock_threshold
```

### Dynamic Forms
```
Test: Checkout multiple parts
1. Go to /checkouts/create
2. Select a work order
3. Select first part and quantity
4. Click "Add Part"
5. Select second part and quantity
6. Submit form
7. Verify both parts are checked out

Test: Remove part row
1. Go to /checkouts/create
2. Click "Add Part" twice (3 rows total)
3. Click "Remove" on middle row
4. Verify row is removed
5. Try to remove last row
6. Verify alert prevents removal
```

### AJAX Features
```
Test: Work order parts loading
1. Go to /checkins/work-order
2. Select a work order with parts
3. Verify part dropdown populates via AJAX
4. Check browser console for errors

Test: Auto-fill from work order
1. Go to /checkouts/create
2. Select a work order
3. Verify technician auto-selects
4. Verify unit number auto-fills
```

### Core Charge Toggles
```
Test: Check-in with core charge
1. Go to /checkins/create
2. Check "This part has a core charge"
3. Verify "Expected Core Rebate" field appears
4. Uncheck the box
5. Verify field hides

Test: Supplier return with core
1. Go to /returns/supplier
2. Check "This is a core charge return"
3. Verify 3 fields appear (amount, expected, received)
4. Uncheck the box
5. Verify fields hide
```

---

## 🎨 UI/UX TESTS

### Responsive Design
```
Test: Mobile navigation
1. Resize browser to mobile width (<640px)
2. Verify navigation items hide
3. Verify content is readable
4. Test on actual mobile device

Test: Table scrolling
1. Resize browser to mobile width
2. Go to /parts
3. Verify table scrolls horizontally
4. All columns are accessible
```

### Visual Feedback
```
Test: Hover states
1. Hover over table rows - verify background changes
2. Hover over buttons - verify color changes
3. Hover over links - verify underline appears

Test: Focus states
1. Tab through form fields
2. Verify focus ring appears (primary color)
3. Verify focus is visible on all inputs

Test: Low stock highlighting
1. Go to /parts
2. Find part with low stock
3. Verify row has red background
4. Verify badge is red with white text
```

### Flash Messages
```
Test: Success message
1. Create a new part
2. Verify green success message appears at top
3. Refresh page
4. Verify message disappears

Test: Error message
1. Try to create part with duplicate Fowler #
2. Verify red error message appears
3. Verify message is clear and helpful
```

---

## 🔍 DATA VALIDATION TESTS

### Required Fields
```
Test: Create part without required fields
1. Go to /parts/create
2. Leave Fowler # blank
3. Try to submit
4. Verify browser validation prevents submit
5. Fill Fowler #, leave Name blank
6. Verify validation prevents submit
```

### Data Types
```
Test: Numeric fields
1. Go to /checkins/create
2. Enter negative number in Quantity
3. Verify validation prevents submit
4. Enter decimal in Quantity
5. Verify behavior (should allow or prevent based on field type)

Test: URL fields
1. Go to /parts/create
2. Enter invalid URL in URL field
3. Verify validation message
4. Enter valid URL
5. Verify form submits
```

### Constraints
```
Test: Unique constraints
1. Create part with Fowler # "TEST-001"
2. Try to create another part with same Fowler #
3. Verify error message
4. Verify first part still exists

Test: Foreign key constraints
1. Try to delete supplier with parts
2. Verify error or cascade behavior
3. Try to delete technician with work orders
4. Verify error or cascade behavior
```

---

## 🔗 INTEGRATION TESTS

### Full Workflow: Receive and Issue Parts
```
1. Create supplier (if not exists)
2. Create part linked to supplier
3. Check-in 10 units of the part
4. Verify inventory shows 10
5. Create work order
6. Checkout 5 units to work order
7. Verify inventory shows 5
8. View work order details
9. Verify 5 units shown
10. Return 2 units from work order
11. Verify inventory shows 7
12. Return 1 unit to supplier
13. Verify inventory shows 6
```

### Core Charge Workflow
```
1. Check-in part with core charge ($50 rebate)
2. Verify check-in recorded
3. Go to /returns
4. Verify core appears in outstanding cores table
5. Click "Process Return"
6. Fill supplier return form
7. Mark rebate as received
8. Submit form
9. Verify core removed from outstanding list
```

---

## 🐛 ERROR HANDLING TESTS

### Database Errors
```
Test: Connection failure
1. Stop database server
2. Try to load /parts
3. Verify graceful error message
4. Restart database
5. Verify app recovers

Test: Query errors
1. Manually corrupt a record
2. Try to view that record
3. Verify error is caught and displayed
```

### Missing Data
```
Test: View non-existent part
1. Go to /parts/99999
2. Verify 404 or "not found" message
3. Verify no PHP errors

Test: Empty lists
1. Clear all parts from database
2. Go to /parts
3. Verify "No parts found" message displays
4. Verify no errors
```

---

## 📊 PERFORMANCE TESTS

### Load Times
```
Test: Large datasets
1. Seed 1000+ parts
2. Load /parts
3. Verify page loads in <2 seconds
4. Test search performance
5. Test filter performance
```

### AJAX Performance
```
Test: Work order parts loading
1. Create WO with 50+ parts
2. Go to /checkins/work-order
3. Select that WO
4. Measure AJAX response time
5. Verify <1 second load
```

---

## ✅ ACCEPTANCE CRITERIA

### Must Pass
- [ ] All forms submit successfully
- [ ] All searches return correct results
- [ ] All filters work as expected
- [ ] Inventory updates correctly
- [ ] No JavaScript console errors
- [ ] No PHP errors or warnings
- [ ] Flash messages display and clear
- [ ] All links navigate correctly
- [ ] Mobile layout is usable
- [ ] QR codes display properly

### Should Pass
- [ ] Page loads in <2 seconds
- [ ] Forms validate before submit
- [ ] Error messages are helpful
- [ ] UI is consistent across pages
- [ ] Hover states work smoothly
- [ ] Tables are readable on mobile
- [ ] AJAX calls complete quickly
- [ ] Dynamic forms work smoothly

### Nice to Have
- [ ] Animations are smooth
- [ ] Loading states display
- [ ] Keyboard navigation works
- [ ] Print views are formatted
- [ ] Export functions work
- [ ] Advanced filters available

---

## 🔧 DEBUGGING TIPS

### Enable PHP Error Display
```php
// Add to public/index.php for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

### Check JavaScript Console
```
F12 → Console tab
Look for:
- Network errors (failed AJAX)
- JavaScript errors
- 404s for missing resources
```

### Database Queries
```php
// Add to Database.php for debugging
echo $sql . "\n";
print_r($params);
```

### Session Debugging
```php
// Add to any view
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
```

---

## 📝 TEST REPORT TEMPLATE

```
Test Date: ___________
Tester: ___________
Environment: Development / Staging / Production

PASSED TESTS:
- [ ] Home & Dashboard
- [ ] Parts Module
- [ ] Suppliers Module
- [ ] Technicians Module
- [ ] Work Orders Module
- [ ] Check-in Module
- [ ] Checkout Module
- [ ] Returns Module

FAILED TESTS:
1. Test Name: ___________
   Expected: ___________
   Actual: ___________
   Steps to Reproduce: ___________

BUGS FOUND:
1. Bug Description: ___________
   Severity: Critical / High / Medium / Low
   Page: ___________
   Browser: ___________

NOTES:
___________
```

---

**Ready to Test!** 🚀

Start with the Quick Start section, then work through each module systematically.
