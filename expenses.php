<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/config/config.php'; require_once __DIR__.'/includes/auth.php'; echo generateCsrf(); ?>">
  <title>Expenses — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireClientLogin();
$client   = getClient();
$clientId = (int)$_SESSION['client_id'];
$year     = (int)($_GET['year'] ?? getTaxYear());

$stmt = db()->prepare("SELECT * FROM expenses WHERE client_id=? AND tax_year=? ORDER BY expense_date DESC");
$stmt->execute([$clientId, $year]);
$records = $stmt->fetchAll();

$total      = array_sum(array_column($records, 'amount'));
$deductible = array_sum(array_column(array_filter($records, fn($r) => $r['is_deductible']), 'amount'));
$years      = range(date('Y'), date('Y') - 5);

$categories = ['General','Office Supplies','Software/Subscriptions','Marketing & Advertising','Travel','Meals & Entertainment','Professional Services','Insurance','Utilities','Rent/Lease','Equipment','Vehicle','Education','Taxes & Licenses','Other'];
?>

<div class="app-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <header class="top-header">
      <button class="header-menu-btn" id="menuBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="header-title">
        <h1>Expenses</h1>
        <p>Track all your business expenses for <?= $year ?></p>
      </div>
      <div class="header-actions">
        <select class="form-control" id="yearSelector" style="width:auto;padding:8px 32px 8px 12px;font-size:13px;font-weight:600;">
          <?php foreach ($years as $y): ?><option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm" onclick="exportTableToCsv('expTable','expenses_<?= $year ?>.csv')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export
        </button>
        <button class="btn btn-danger btn-sm" data-open-modal="addExpModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Expense
        </button>
      </div>
    </header>

    <div class="page-body">
      <div id="alertContainer"></div>

      <!-- Summary Cards -->
      <div class="grid grid-3 mb-24">
        <div class="stat-card" style="--accent:var(--danger)">
          <div class="stat-icon" style="background:#FEF2F2;color:var(--danger)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Expenses</div>
            <div class="stat-value text-danger"><?= formatCurrency($total) ?></div>
            <div class="stat-change down"><?= count($records) ?> records in <?= $year ?></div>
          </div>
        </div>
        <div class="stat-card" style="--accent:var(--success)">
          <div class="stat-icon" style="background:#ECFDF5;color:var(--success)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Tax Deductible</div>
            <div class="stat-value text-success"><?= formatCurrency($deductible) ?></div>
            <div class="stat-change up"><?= $total > 0 ? round(($deductible/$total)*100) : 0 ?>% of total</div>
          </div>
        </div>
        <div class="stat-card" style="--accent:var(--orange)">
          <div class="stat-icon" style="background:#FFF4EE;color:var(--orange)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Categories Used</div>
            <div class="stat-value text-orange"><?= count(array_unique(array_column($records,'category'))) ?></div>
            <div class="stat-change up">of <?= count($categories) ?> available</div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card">
        <div class="card-header" style="padding:20px 24px;flex-wrap:wrap;gap:12px;">
          <span class="card-title">Expense Records</span>
          <div class="table-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="tableSearch" placeholder="Search expenses...">
          </div>
        </div>
        <div class="table-wrap">
          <?php if (empty($records)): ?>
            <div class="empty-state" style="padding:60px">
              <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg></div>
              <h3>No expenses yet</h3>
              <p>Start tracking your business expenses for <?= $year ?></p>
              <button class="btn btn-danger" data-open-modal="addExpModal">Add First Expense</button>
            </div>
          <?php else: ?>
            <table class="data-table" id="expTable">
              <thead>
                <tr>
                  <th>Date</th><th>Description</th><th>Category</th><th>Vendor</th>
                  <th>Deductible</th><th class="text-right">Amount</th><th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($records as $r): ?>
                  <tr>
                    <td style="white-space:nowrap;color:var(--gray-500);font-size:13px"><?= formatDate($r['expense_date']) ?></td>
                    <td>
                      <div style="font-weight:600;color:var(--blue)"><?= sanitize($r['description']) ?></div>
                      <?php if ($r['reference']): ?><div style="font-size:12px;color:var(--gray-400)">Ref: <?= sanitize($r['reference']) ?></div><?php endif; ?>
                    </td>
                    <td><span class="badge badge-warning"><?= sanitize($r['category']) ?></span></td>
                    <td style="font-size:13px;color:var(--gray-500)"><?= sanitize($r['vendor'] ?: '—') ?></td>
                    <td class="text-center">
                      <?php if ($r['is_deductible']): ?>
                        <span class="badge badge-success">Yes</span>
                      <?php else: ?>
                        <span class="badge badge-danger">No</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right"><span style="font-size:15px;font-weight:700;color:var(--danger)">-<?= formatCurrency((float)$r['amount']) ?></span></td>
                    <td class="text-center">
                      <div style="display:flex;gap:6px;justify-content:center">
                        <button class="btn btn-ghost btn-icon" onclick="editExp(<?= $r['id'] ?>)" data-tooltip="Edit">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="deleteExp(<?= $r['id'] ?>)" data-tooltip="Delete">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr style="background:var(--gray-50)">
                  <td colspan="5" style="padding:14px 16px;font-weight:700;color:var(--blue)">Total Expenses</td>
                  <td class="text-right" style="padding:14px 16px;font-size:16px;font-weight:800;color:var(--danger)"><?= formatCurrency($total) ?></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Expense Modal -->
<div class="modal-backdrop" id="addExpModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Expense</span>
      <button class="modal-close" data-close-modal="addExpModal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form id="addExpForm">
        <div class="form-group">
          <label class="form-label">Description *</label>
          <input type="text" name="description" class="form-control" placeholder="e.g. Office rent — January" required>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Amount (£) *</label>
            <div class="input-group"><span class="input-prefix">£</span>
              <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category" class="form-control">
              <?php foreach ($categories as $c): ?><option><?= $c ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Vendor</label>
            <input type="text" name="vendor" class="form-control" placeholder="Vendor name">
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-control">
              <option value="">— Select —</option>
              <option>Cash</option><option>Credit Card</option><option>Debit Card</option>
              <option>Bank Transfer</option><option>Check</option><option>PayPal</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tax Year</label>
            <select name="tax_year" class="form-control">
              <?php foreach ($years as $y): ?><option <?= $y===$year?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reference / Receipt #</label>
          <input type="text" name="reference" class="form-control" placeholder="Invoice or receipt number">
        </div>
        <div class="form-group">
          <label class="checkbox-wrap">
            <input type="checkbox" name="is_deductible" value="1" checked>
            <span style="font-size:13px;font-weight:600;color:var(--blue)">This is a tax-deductible expense</span>
          </label>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal="addExpModal">Cancel</button>
      <button class="btn btn-danger" id="saveExpBtn" onclick="saveExpense()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
        Save Expense
      </button>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
initTableSearch('tableSearch','expTable');

async function saveExpense() {
  const form = document.getElementById('addExpForm');
  const fd   = new FormData(form);
  const data = Object.fromEntries(fd);
  data.is_deductible = fd.has('is_deductible') ? 1 : 0;
  if (!data.description || !data.amount) { showAlert('Fill required fields.','danger'); return; }
  const btn = document.getElementById('saveExpBtn');
  setLoading(btn, true);
  const result = await apiCall('/api/expenses.php', { action:'create', ...data });
  setLoading(btn, false);
  if (result.success) { toast('Expense added!','success'); setTimeout(()=>location.reload(),700); }
  else showAlert(result.message,'danger');
}

async function editExp(id) {
  const result = await apiCall('/api/expenses.php', { action:'get', id });
  if (result.success) { toast('Edit coming soon – feature in progress','info'); }
}

async function deleteExp(id) {
  if (!confirm('Delete this expense?')) return;
  const result = await apiCall('/api/expenses.php', { action:'delete', id });
  if (result.success) { toast('Deleted!','success'); setTimeout(()=>location.reload(),600); }
  else toast(result.message,'danger');
}
</script>
</body>
</html>
