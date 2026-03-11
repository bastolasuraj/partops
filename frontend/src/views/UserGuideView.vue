<template>
  <div class="guide-page">
    <section class="guide-hero">
      <h1>PAM User Guide</h1>
      <div class="hero-tabs hero-tabs-desktop">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          class="guide-tab hero-tab"
          :class="{ active: activeTab === tab.id }"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>
      <div class="hero-tabs-mobile" aria-label="Guide sections">
        <div class="mobile-current-tab">{{ activeTabLabel }}</div>

        <div class="mobile-pager">
          <button
            type="button"
            class="mobile-nav-button"
            :disabled="!previousTab"
            aria-label="Previous section"
            @click="goToPreviousTab"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M15 18l-6-6 6-6" />
            </svg>
          </button>

          <template v-for="(tab, index) in mobilePagerTabs" :key="tab.id">
            <button
              type="button"
              class="mobile-pager-link"
              @click="activeTab = tab.id"
            >
              {{ tab.label }}
            </button>

            <span v-if="index === 0" class="mobile-pager-divider" aria-hidden="true"></span>
          </template>

          <button
            type="button"
            class="mobile-nav-button"
            :disabled="!nextTab"
            aria-label="Next section"
            @click="goToNextTab"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M9 6l6 6-6 6" />
            </svg>
          </button>
        </div>
      </div>
    </section>

    <section v-show="activeTab === 'start'" class="guide-panel">
      <div class="panel-head">
        <h2>Start Here</h2>
      </div>
      <div class="panel-body">
        <div class="note ok">
          Think of PAM as a stock movement tool. Parts come in, go out, come back, or go back to a vendor.
          Every one of those moves is saved in the system automatically.
        </div>

        <h3>The 4 Main Flows</h3>
        <div class="flow-grid">
          <div class="flow-box">
            <span class="flow-label">1. Receive</span>
            <strong>Incoming - Vendor (New)</strong>
            <p>Add brand-new stock from a supplier.</p>
          </div>
          <div class="flow-arrow">→</div>
          <div class="flow-box">
            <span class="flow-label">2. Issue</span>
            <strong>Outgoing</strong>
            <p>Check parts out to a work order, unit, or technician.</p>
          </div>
          <div class="flow-arrow">→</div>
          <div class="flow-box">
            <span class="flow-label">3. Return to Shelf</span>
            <strong>Incoming - Return Tabs</strong>
            <p>Put issued parts back into stock.</p>
          </div>
          <div class="flow-arrow">→</div>
          <div class="flow-box">
            <span class="flow-label">4. Return to Vendor</span>
            <strong>Return to Vendor</strong>
            <p>Send stock back to a supplier.</p>
          </div>
        </div>

        <h3>Important Rules</h3>
        <ul>
          <li>A part must exist in <code>Parts Master</code> before PAM lets you use it in Incoming or Outgoing.</li>
          <li>If a typed value is not valid, the field turns red, a warning appears, and the submit button stays disabled.</li>
          <li>When checking out from Outgoing, one basket line can use only one location at a time.</li>
          <li>If stock is split across two or more locations, users must do separate transactions.</li>
          <li>If a technician is created without an employee number, PAM creates a temporary 10-digit number.</li>
          <li>Old parts are archived rather than permanently erased.</li>
        </ul>
      </div>
    </section>

    <section v-show="activeTab === 'navigation'" class="guide-panel">
      <div class="panel-head">
        <h2>Navigation</h2>
      </div>
      <div class="panel-body">
        <h3>Desktop Navigation</h3>
        <table class="guide-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>What Users Do Here</th>
            </tr>
          </thead>
          <tbody>
            <tr><td><strong>Dashboard</strong></td><td>See a quick summary of stock, transactions, suppliers, alerts, and shortcuts.</td></tr>
            <tr><td><strong>Parts Master</strong></td><td>Manage the parts list and part details.</td></tr>
            <tr><td><strong>Master Data</strong></td><td>Open <strong>Suppliers</strong>, <strong>Technicians</strong>, and <strong>Units</strong>.</td></tr>
            <tr><td><strong>Asset Ops</strong></td><td>Open <strong>Incoming</strong>, <strong>Outgoing</strong>, <strong>Return to Vendor</strong>, <strong>Transactions</strong>, and <strong>Reports</strong>.</td></tr>
            <tr><td><strong>User Menu</strong></td><td>Open account options and log out. Admins also see settings and test tools.</td></tr>
          </tbody>
        </table>

        <h3>Mobile Menu</h3>
        <p>
          On mobile, use the top-right menu button. It includes the same main areas as desktop and
          also adds a few quick links that are useful on a phone or tablet.
        </p>
        <table class="guide-table">
          <thead>
            <tr>
              <th>Mobile Link</th>
              <th>Why It Matters</th>
            </tr>
          </thead>
          <tbody>
            <tr><td><strong>Work Orders</strong></td><td>Quick access to work order history and the parts used on them.</td></tr>
            <tr><td><strong>QR Scanner</strong></td><td>Fast part lookup by camera scan.</td></tr>
            <tr><td><strong>Low Stock</strong></td><td>Quick reorder/attention list.</td></tr>
          </tbody>
        </table>

        <h3>Other Useful Pages</h3>
        <div class="card-grid">
          <div class="guide-card">
            <strong>Vendor Returns History</strong>
            <p>Review supplier return records after they are created.</p>
          </div>
          <div class="guide-card">
            <strong>Fowler PN Mapping</strong>
            <p>See which supplier part numbers are grouped under the same Fowler part number.</p>
          </div>
          <div class="guide-card">
            <strong>Site Map</strong>
            <p>Open a full-page directory of the visible pages in the app.</p>
          </div>
        </div>
      </div>
    </section>

    <section v-show="activeTab === 'daily'" class="guide-panel">
      <div class="panel-head">
        <h2>Daily Tasks</h2>
      </div>
      <div class="panel-body">
        <h3>Dashboard</h3>
        <p>
          The dashboard is the quickest way to see what needs attention. It shows total parts, current
          stock value, low stock items, supplier count, today's transactions, recent activity, and quick action buttons.
        </p>

        <h3>Incoming</h3>
        <div class="note ok">
          Incoming has four tabs: <strong>Vendor (New)</strong>, <strong>WO Return</strong>,
          <strong>Unit Return</strong>, and <strong>Tech Return</strong>.
        </div>

        <div class="card-grid">
          <div class="guide-card">
            <strong>Vendor (New)</strong>
            <ul>
              <li>Use this when brand-new stock arrives from a supplier.</li>
              <li>Enter the part, vendor, quantity, unit price, and storage location.</li>
              <li>You can also enter core-related amounts if needed.</li>
              <li>After submit, stock goes up and the new receipt becomes part of your history.</li>
            </ul>
          </div>
          <div class="guide-card">
            <strong>WO Return / Unit Return / Tech Return</strong>
            <ul>
              <li>Use these when a checked-out part is coming back to shelf.</li>
              <li>Choose the correct work order, unit, or technician first.</li>
              <li>PAM shows the items that are available to return.</li>
              <li>You cannot return more than what is still available to return.</li>
            </ul>
          </div>
          <div class="guide-card">
            <strong>Manual Return Mode</strong>
            <ul>
              <li>Use this only when the normal tracked return flow is not enough.</li>
              <li>Add one or more part rows manually.</li>
              <li>PAM checks each typed part as soon as you leave the field.</li>
              <li>If a part is not registered, the page warns you immediately and blocks submission.</li>
            </ul>
          </div>
        </div>

        <h4>Incoming Validation Users Will Notice</h4>
        <ul>
          <li>Typed parts, vendors, work orders, units, and technicians all validate when the user tabs or clicks away from the field.</li>
          <li>If the typed value matches exactly, PAM selects it automatically.</li>
          <li>If it does not exist, the page shows a warning and the action button stays red and disabled.</li>
          <li>When allowed by admin settings, users can create missing vendors, work orders, units, or technicians from inside the page.</li>
        </ul>

        <div class="flow-grid">
          <div class="flow-box">
            <strong>Receive New Stock</strong>
            <p>Select part and vendor, then enter quantity, price, and location.</p>
          </div>
          <div class="flow-arrow">→</div>
          <div class="flow-box">
            <strong>PAM Checks</strong>
            <p>Part exists, vendor exists, quantity is valid, and price is filled in.</p>
          </div>
          <div class="flow-arrow">→</div>
          <div class="flow-box">
            <strong>Result</strong>
            <p>Stock increases, location stock updates, and the receipt is saved in history.</p>
          </div>
        </div>

        <h3>Outgoing</h3>
        <ul>
          <li>Select whether the part is going to a work order, unit, or technician.</li>
          <li>Add one or more parts to the basket.</li>
          <li>Enter quantity and choose the source location if the part exists in more than one location.</li>
          <li>The button stays disabled until the destination, part, quantity, and location rules are all valid.</li>
        </ul>
        <div class="note warn">
          Outgoing does not auto-pull from multiple locations. If a part is stocked in more than one
          place, pick one location and do separate transactions if more than one location must be used.
        </div>

        <div class="flow-grid">
          <div class="flow-box">
            <strong>Check Out</strong>
            <p>Choose destination, part, quantity, and location.</p>
          </div>
          <div class="flow-arrow">→</div>
          <div class="flow-box">
            <strong>PAM Checks</strong>
            <p>Destination exists, part exists, enough stock is available, and one location is chosen if needed.</p>
          </div>
          <div class="flow-arrow">→</div>
          <div class="flow-box">
            <strong>Result</strong>
            <p>Stock goes down and the movement is saved in the transaction history.</p>
          </div>
        </div>

        <h3>Return to Vendor</h3>
        <ul>
          <li>Use this when a part is leaving PAM and going back to the supplier.</li>
          <li>Choose the part and supplier, enter quantity, add an RMA number if there is one, and add notes if needed.</li>
          <li>PAM will not allow the return quantity to be greater than current stock.</li>
          <li>After submit, stock is reduced and the supplier return is saved for later review.</li>
        </ul>
      </div>
    </section>

    <section v-show="activeTab === 'setup'" class="guide-panel">
      <div class="panel-head">
        <h2>Setup Records</h2>
      </div>
      <div class="panel-body">
        <h3>Parts Master</h3>
        <ul>
          <li>This is where parts are created, edited, searched, imported, and archived.</li>
          <li>Important details include supplier, supplier part number, Fowler part number, part name, unit of measure, and location details.</li>
          <li>Low stock threshold controls when the part appears on low stock views.</li>
          <li>Stock is not typed directly here. PAM calculates it from movements.</li>
        </ul>
        <div class="note">
          If a part does not exist here, Incoming and Outgoing will reject it.
        </div>

        <h3>Suppliers</h3>
        <p>
          Stores supplier name and contact details. These records are used when receiving new stock
          and when returning parts to vendors.
        </p>

        <h3>Technicians</h3>
        <p>
          Stores technician name, role, employee number, phone, and email. If employee number is left
          blank during creation, PAM generates a temporary 10-digit number.
        </p>

        <h3>Units</h3>
        <p>
          Stores unit ID, make, model, year, VIN, and plate so parts can be checked out to the correct unit.
        </p>

        <h3>Work Orders</h3>
        <p>
          Work orders are used as a destination in Outgoing and as a reference in Incoming return tabs.
          Users can also review work order history and parts used from the Work Orders page.
        </p>

        <h3>When Can Users Create Missing Records Inside a Task Page?</h3>
        <p>
          If admins have enabled contingency mode, users may see create options for missing vendors,
          work orders, units, or technicians while working in Incoming or Outgoing. If that mode is off,
          those records must be created in their normal pages first.
        </p>
      </div>
    </section>

    <section v-show="activeTab === 'review'" class="guide-panel">
      <div class="panel-head">
        <h2>Review Tools</h2>
      </div>
      <div class="panel-body">
        <h3>Transactions</h3>
        <ul>
          <li>Shows the movement history of parts.</li>
          <li>Users can filter by part number, transaction type, reference type, and date range.</li>
          <li>Rows can be expanded to see part details, quantity, location, unit cost, total cost, and notes.</li>
        </ul>

        <h3>Reports</h3>
        <ul>
          <li>Supports date range filters and quick ranges like Last 30 Days, This Month, and All Time.</li>
          <li>Can generate user, supplier, transaction, and parts reports.</li>
          <li>Downloads are available in XLSX and PDF.</li>
          <li>Downloaded reports are also saved so they can be downloaded again later from the archived list.</li>
        </ul>

        <h3>Low Stock</h3>
        <p>
          Highlights parts that are out of stock or at or below their low stock threshold. Users can go straight
          from here to Incoming to add stock.
        </p>

        <h3>QR Scanner</h3>
        <p>
          Uses the device camera to scan a label and open the matching part. If the part has stock available,
          the user can move directly to Outgoing from the scan result.
        </p>

        <h3>Fowler PN Mapping</h3>
        <p>
          Helps users see which supplier part numbers are grouped under a Fowler number. This is useful when the
          same item can be purchased from more than one supplier.
        </p>

        <h3>Site Map</h3>
        <p>
          A simple page that lists the visible pages in one place for users who prefer a full-page directory.
        </p>
      </div>
    </section>

    <section v-show="activeTab === 'admin'" class="guide-panel">
      <div class="panel-head">
        <h2>Admin Notes</h2>
      </div>
      <div class="panel-body">
        <h3>Settings</h3>
        <ul>
          <li>Admins can control contingency mode for manual/untracked returns and quick creation inside operational pages.</li>
          <li>Admins also have access to reset or cleanup tools intended for development and testing.</li>
        </ul>

        <h3>What Users Should Know</h3>
        <ul>
          <li>Everything done in PAM is logged automatically.</li>
          <li>Buttons turning red and disabled usually mean the page is protecting stock accuracy.</li>
          <li>If a value will not validate, first check whether the record exists in Parts Master, Suppliers, Units, Technicians, or Work Orders.</li>
          <li>If a part exists in more than one location, users must choose the correct location when checking out.</li>
        </ul>

        <div class="note warn">
          Best practice: keep master records clean before doing transactions. Clean setup makes Incoming,
          Outgoing, and reporting much easier for everyone.
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const tabs = [
  { id: 'start', label: 'Start Here' },
  { id: 'navigation', label: 'Navigation' },
  { id: 'daily', label: 'Daily Tasks' },
  { id: 'setup', label: 'Setup Records' },
  { id: 'review', label: 'Review Tools' },
  { id: 'admin', label: 'Admin Notes' }
]

const activeTab = ref('start')
const activeTabIndex = computed(() => tabs.findIndex((tab) => tab.id === activeTab.value))
const activeTabLabel = computed(() => tabs[activeTabIndex.value]?.label || '')
const previousTab = computed(() => tabs[activeTabIndex.value - 1] || null)
const nextTab = computed(() => tabs[activeTabIndex.value + 1] || null)
const mobilePagerTabs = computed(() => {
  const currentIndex = activeTabIndex.value
  const lastIndex = tabs.length - 1

  if (currentIndex <= 0) {
    return tabs.slice(1, 3)
  }

  if (currentIndex >= lastIndex) {
    return tabs.slice(Math.max(0, lastIndex - 2), lastIndex)
  }

  return [tabs[currentIndex - 1], tabs[currentIndex + 1]]
})

const goToPreviousTab = () => {
  if (activeTabIndex.value <= 0) {
    return
  }

  activeTab.value = tabs[activeTabIndex.value - 1].id
}

const goToNextTab = () => {
  if (activeTabIndex.value >= tabs.length - 1) {
    return
  }

  activeTab.value = tabs[activeTabIndex.value + 1].id
}
</script>

<style scoped>
.guide-page {
  max-width: 1180px;
  margin: 0 auto;
}

.guide-hero,
.guide-panel {
  background: #ffffff;
  border: 1px solid #d7e2ec;
  border-radius: 18px;
  box-shadow: 0 14px 28px rgba(15, 76, 129, 0.08);
}

.guide-hero {
  padding: 24px;
  background: linear-gradient(135deg, #0f4c81, #1c6fb4);
  color: #ffffff;
  margin-bottom: 16px;
}

.guide-hero h1 {
  margin: 0 0 18px;
  font-size: 2rem;
  line-height: 1.15;
}

.hero-tabs-desktop {
  display: flex;
  flex-wrap: nowrap;
  gap: 10px;
  align-items: stretch;
}

.hero-tabs-desktop .guide-tab {
  flex: 1 1 0;
}

.hero-tabs-mobile {
  display: none;
  width: 100%;
  flex-direction: column;
  gap: 12px;
}

.mobile-current-tab {
  width: 100%;
  text-align: center;
  font-size: 1.05rem;
  font-weight: 700;
  color: #ffffff;
}

.mobile-pager {
  width: 100%;
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr) auto minmax(0, 1fr) 44px;
  align-items: center;
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.12);
  overflow: hidden;
}

.mobile-nav-button {
  width: 44px;
  height: 44px;
  border: 0;
  background: transparent;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  transition: background 0.2s ease, opacity 0.2s ease;
}

.mobile-nav-button svg {
  width: 18px;
  height: 18px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2.2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.mobile-nav-button:not(:disabled):hover {
  background: rgba(255, 255, 255, 0.08);
}

.mobile-nav-button:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.mobile-pager-link {
  background: transparent;
  border: 0;
  min-width: 0;
  padding: 0 8px;
  color: rgba(255, 255, 255, 0.78);
  font-size: 0.95rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: center;
}

.mobile-pager-link:not(:disabled):hover {
  color: #ffffff;
}

.mobile-pager-link:disabled {
  color: rgba(255, 255, 255, 0.38);
  cursor: default;
}

.mobile-pager-divider {
  width: 1px;
  height: 18px;
  background: rgba(255, 255, 255, 0.32);
  flex: 0 0 auto;
}

.guide-tab {
  border: 1px solid #d7e2ec;
  background: #f8fbff;
  color: #23425f;
  border-radius: 12px;
  padding: 10px 12px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s ease;
}

.guide-tab:hover {
  background: #eef6fd;
}

.guide-tab.active {
  background: #0f4c81;
  border-color: #0f4c81;
  color: #ffffff;
}

.hero-tab {
  border-color: rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
}

.hero-tab:hover {
  background: rgba(255, 255, 255, 0.18);
}

.hero-tab.active {
  background: #ffffff;
  border-color: #ffffff;
  color: #0f4c81;
}

.guide-panel {
  overflow: hidden;
}

.panel-head {
  padding: 18px 20px 10px;
  border-bottom: 1px solid #d7e2ec;
  background: linear-gradient(180deg, #f8fbff, #ffffff);
}

.panel-body {
  padding: 18px 20px 20px;
}

.panel-head h2,
.panel-body h3,
.panel-body h4 {
  color: #133a59;
}

.panel-head h2 {
  margin: 0;
  font-size: 1.45rem;
}

.panel-body h3 {
  margin: 18px 0 8px;
  font-size: 1.05rem;
}

.panel-body h4 {
  margin: 14px 0 8px;
  font-size: 0.98rem;
  color: #1b4d74;
}

.panel-body p {
  margin: 0 0 12px;
  color: #5f6c7b;
}

.panel-body ul,
.panel-body ol {
  margin: 8px 0 12px 20px;
  padding: 0;
  color: #5f6c7b;
}

.panel-body li {
  margin-bottom: 6px;
}

.note {
  border-left: 4px solid #0f4c81;
  background: #ebf4fc;
  color: #18496d;
  padding: 12px 14px;
  border-radius: 10px;
  margin: 12px 0;
}

.note.ok {
  background: #e8f8ef;
  border-left-color: #0f7a46;
  color: #155132;
}

.note.warn {
  background: #fff4e7;
  border-left-color: #b45309;
  color: #7a400d;
}

.card-grid,
.flow-grid {
  display: grid;
  gap: 12px;
}

.card-grid {
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.guide-card {
  border: 1px solid #d7e2ec;
  border-radius: 14px;
  padding: 14px;
  background: #ffffff;
}

.guide-card strong {
  display: block;
  margin-bottom: 8px;
  color: #173d5b;
}

.flow-grid {
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  align-items: stretch;
  margin: 14px 0;
}

.flow-box {
  border: 1px solid #d7e2ec;
  border-radius: 14px;
  padding: 14px;
  background: #ffffff;
  min-height: 120px;
}

.flow-box strong {
  display: block;
  margin-bottom: 6px;
  color: #173d5b;
}

.flow-label {
  display: inline-block;
  padding: 4px 9px;
  border-radius: 999px;
  background: #ebf4fc;
  color: #0f4c81;
  font-size: 0.82rem;
  font-weight: 700;
  margin-bottom: 8px;
}

.flow-arrow {
  text-align: center;
  font-size: 1.6rem;
  color: #0f4c81;
  align-self: center;
}

.guide-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  font-size: 0.94rem;
}

.guide-table th,
.guide-table td {
  border: 1px solid #d7e2ec;
  padding: 10px;
  text-align: left;
  vertical-align: top;
  color: #5f6c7b;
}

.guide-table th {
  background: #f4f8fc;
  color: #153d5f;
  font-weight: 700;
}

code {
  font-family: Consolas, "Courier New", monospace;
  background: #f2f6fb;
  border: 1px solid #d8e4ef;
  border-radius: 6px;
  padding: 1px 6px;
  color: #0f4c81;
  font-size: 0.92em;
}

@media (max-width: 760px) {
  .guide-hero {
    padding-left: 14px;
    padding-right: 14px;
  }

  .guide-hero h1 {
    font-size: 1.58rem;
  }

  .hero-tabs-desktop {
    display: none;
  }

  .hero-tabs-mobile {
    display: flex;
  }

  .flow-arrow {
    transform: rotate(90deg);
  }

  .panel-head,
  .panel-body {
    padding-left: 14px;
    padding-right: 14px;
  }
}
</style>
