<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/config/config.php'; require_once __DIR__.'/includes/auth.php'; echo generateCsrf(); ?>">
  <title>Income — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    /* Summary card */
    .income-summary { display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
    .income-total   { flex:1; min-width:180px; }
    .income-stats   { display:flex; gap:12px; flex-wrap:wrap; }
    .income-stat-box { text-align:center; padding:12px 20px; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
    .income-stat-box .snum { font-size:22px; font-weight:800; color:var(--blue); }
    .income-stat-box .slbl { font-size:12px; color:var(--gray-500); }

    /* Header actions on mobile */
    @media (max-width: 600px) {
      .header-actions { flex-wrap:wrap; justify-content:flex-end; }
      .header-actions .btn-sm span { display:none; }
    }

    /* Table: hide non-critical columns on mobile */
    @media (max-width: 768px) {
      /* Hide: Company(3), Payment Method(5), Tax Year(6), Document(7), Other Docs(8), Notes(9) */
      #incomeTable th:nth-child(3), #incomeTable td:nth-child(3),
      #incomeTable th:nth-child(5), #incomeTable td:nth-child(5),
      #incomeTable th:nth-child(6), #incomeTable td:nth-child(6),
      #incomeTable th:nth-child(7), #incomeTable td:nth-child(7),
      #incomeTable th:nth-child(8), #incomeTable td:nth-child(8),
      #incomeTable th:nth-child(9), #incomeTable td:nth-child(9) { display:none; }
      #incomeTable tfoot { display:none; }

      .income-summary { flex-direction:column; align-items:flex-start; gap:14px; }
      .income-stats   { width:100%; }
      .income-stat-box { flex:1; padding:10px 12px; }
    }

    @media (max-width: 480px) {
      /* Also hide Category(4) on very small screens */
      #incomeTable th:nth-child(4), #incomeTable td:nth-child(4) { display:none; }
      .income-stat-box .snum { font-size:18px; }
      .income-stat-box { padding:8px 10px; }
    }
  </style>
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

// Fetch income records
$stmt = db()->prepare("SELECT * FROM income WHERE client_id=? AND tax_year=? ORDER BY income_date DESC");
$stmt->execute([$clientId, $year]);
$records = $stmt->fetchAll();

$total = array_sum(array_column($records, 'amount'));
$years = range(date('Y'), date('Y') - 5);

$categories = [
    'Employment Income',
    'Self-Employment / Freelance',
    'Trading Income',
    'Property / Rental Income',
    'Dividends',
    'Savings & Interest',
    'Pension Income',
    'Partnership Income',
    'Capital Gains',
    'Grant / Subsidy',
    'Foreign Income',
    'Other Income',
];
?>

<div class="app-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <header class="top-header">
      <button class="header-menu-btn" id="menuBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="header-title">
        <h1>Income</h1>
        <p>Track all your income sources for <?= $year ?></p>
      </div>
      <div class="header-actions">
        <select class="form-control" id="yearSelector" style="width:auto;padding:8px 32px 8px 12px;font-size:13px;font-weight:600;">
          <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm" onclick="exportTableToCsv('incomeTable','income_<?= $year ?>.csv')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export
        </button>
        <button class="btn btn-primary btn-sm" data-open-modal="addIncomeModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Income
        </button>
      </div>
    </header>

    <div class="page-body">
      <div id="alertContainer"></div>

      <!-- Summary Card -->
      <div class="card mb-24" style="background:linear-gradient(135deg,#ECFDF5 0%,#F0FFF8 100%);border-color:#A7F3D0;">
        <div class="card-body income-summary">
          <div class="income-total">
            <div style="font-size:13px;color:var(--success);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Total Income — <?= $year ?></div>
            <div style="font-size:36px;font-weight:800;color:#065f46"><?= formatCurrency($total) ?></div>
          </div>
          <div class="income-stats">
            <div class="income-stat-box">
              <div class="snum"><?= count($records) ?></div>
              <div class="slbl">Entries</div>
            </div>
            <div class="income-stat-box">
              <div class="snum"><?= count(array_unique(array_column($records,'category'))) ?></div>
              <div class="slbl">Categories</div>
            </div>
            <?php if (count($records) > 0): ?>
            <div class="income-stat-box">
              <div class="snum"><?= formatCurrency($total / count($records)) ?></div>
              <div class="slbl">Avg. Entry</div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="card">
        <div class="card-header" style="padding:20px 24px;flex-wrap:wrap;gap:12px;">
          <span class="card-title">Income Records</span>
          <div class="table-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="tableSearch" placeholder="Search income...">
          </div>
        </div>
        <div class="table-wrap">
          <?php if (empty($records)): ?>
            <div class="empty-state" style="padding:60px 24px">
              <div class="empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
              </div>
              <h3>No income records yet</h3>
              <p>Start tracking your income for <?= $year ?></p>
              <button class="btn btn-primary" data-open-modal="addIncomeModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add First Income
              </button>
            </div>
          <?php else: ?>
            <table class="data-table" id="incomeTable">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Company</th>
                  <th>Category</th>
                  <th>Payment Method</th>
                  <th>Tax Year</th>
                  <th>Document</th>
                  <th>Other Documents</th>
                  <th>Notes</th>
                  <th class="text-right">Amount</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($records as $r): ?>
                  <tr>
                    <td style="white-space:nowrap;color:var(--gray-500);font-size:13px"><?= formatDate($r['income_date']) ?></td>
                    <td><div style="font-weight:600;color:var(--blue)"><?= sanitize($r['description']) ?></div></td>
                    <td style="font-size:13px;color:var(--gray-600)"><?= sanitize($r['reference'] ?: '—') ?></td>
                    <td><span class="badge badge-success"><?= sanitize($r['category']) ?></span></td>
                    <td style="font-size:13px;color:var(--gray-500)"><?= sanitize($r['payment_method'] ?: '—') ?></td>
                    <td style="font-size:13px;color:var(--gray-500)"><?= sanitize($r['tax_year'] ?: '—') ?></td>
                    <td style="font-size:12px;max-width:160px;">
                      <?php if ($r['document_type']): ?>
                        <div style="color:var(--gray-600);margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= sanitize($r['document_type']) ?>"><?= sanitize(substr($r['document_type'],0,30)) ?></div>
                      <?php endif; ?>
                      <?php if ($r['receipt_file']): ?>
                        <a href="/assets/uploads/income/<?= sanitize($r['receipt_file']) ?>" target="_blank" style="color:var(--orange);font-weight:600;font-size:11px;">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                          View file
                        </a>
                      <?php else: ?>
                        <span style="color:var(--gray-400)">—</span>
                      <?php endif; ?>
                    </td>
                    <td style="font-size:12px;max-width:160px;">
                      <?php if (!empty($r['other_document_label'])): ?>
                        <div style="color:var(--gray-600);margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= sanitize($r['other_document_label']) ?>"><?= sanitize(substr($r['other_document_label'],0,30)) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($r['other_document_file'])): ?>
                        <a href="/assets/uploads/income/<?= sanitize($r['other_document_file']) ?>" target="_blank" style="color:var(--orange);font-weight:600;font-size:11px;">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                          View file
                        </a>
                      <?php elseif(empty($r['other_document_label'])): ?>
                        <span style="color:var(--gray-400)">—</span>
                      <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--gray-400);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= sanitize($r['notes']) ?>"><?= sanitize($r['notes'] ? substr($r['notes'],0,50) : '—') ?></td>
                    <td class="text-right"><span style="font-size:15px;font-weight:700;color:var(--success)">+<?= formatCurrency((float)$r['amount']) ?></span></td>
                    <td class="text-center">
                      <div style="display:flex;gap:6px;justify-content:center">
                        <button class="btn btn-ghost btn-icon" onclick="editIncome(<?= $r['id'] ?>)" data-tooltip="Edit">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="deleteRecord('income',<?= $r['id'] ?>)" data-tooltip="Delete">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr style="background:var(--gray-50)">
                  <td colspan="9" style="padding:14px 16px;font-weight:700;color:var(--blue)">Total</td>
                  <td class="text-right" style="padding:14px 16px;font-size:16px;font-weight:800;color:var(--success)"><?= formatCurrency($total) ?></td>
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

<!-- Add Income Modal -->
<div class="modal-backdrop" id="addIncomeModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Income Entry</span>
      <button class="modal-close" data-close-modal="addIncomeModal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form id="addIncomeForm">
        <div class="form-group">
          <label class="form-label">Description *</label>
          <input type="text" name="description" class="form-control" placeholder="e.g. Invoice #1234 — Client Name" required>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Amount (£) *</label>
            <div class="input-group">
              <span class="input-prefix">£</span>
              <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="income_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
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
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-control">
              <option value="">— Select —</option>
              <option>Bank Transfer (BACS/CHAPS)</option>
              <option>Faster Payments</option>
              <option>Cash</option>
              <option>Cheque</option>
              <option>Direct Debit</option>
              <option>Standing Order</option>
              <option>Credit/Debit Card</option>
              <option>PayPal</option>
              <option>Other</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Tax Year</label>
          <select name="tax_year" class="form-control">
            <?php foreach ($years as $y): ?><option <?= $y===$year?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">🏢 Which company? (optional)</label>
          <input type="text" name="reference" class="form-control" placeholder="e.g. Uber, Bolt, local firm...">
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Document Type (optional)</label>
            <select name="document_type" class="form-control">
              <option value="">— Select document type —</option>
              <option>Weekly or monthly income reports/Summary</option>
              <option>Cash job records</option>
              <option>Payslips (Employed Individuals)</option>
              <option>Rental income</option>
              <option>Benefits or grants</option>
              <option>Interest income</option>
              <option>Side business income</option>
              <option>Other Documents</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Upload File (PNG / PDF)</label>
            <input type="file" name="receipt_file" class="form-control" accept=".png,.pdf,image/png,application/pdf">
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Other key documents (optional)</label>
            <select name="other_document_label" class="form-control">
              <option value="">— Select document —</option>
              <option>Signed 64-8 Form (authorising tax agent)</option>
              <option>HMRC Digital Authorization Approval</option>
              <option>Bank statements (business-related income)</option>
              <option>P60 / P45 form (Employed Individuals)</option>
              <option>Signed Tax Declaration Form</option>
              <option>Previous Year tax return</option>
              <option>Other Documents</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Upload File (PNG / PDF)</label>
            <input type="file" name="other_document_file" class="form-control" accept=".png,.pdf,image/png,application/pdf">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" placeholder="Optional notes..." rows="2"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal="addIncomeModal">Cancel</button>
      <button class="btn btn-primary" id="saveIncomeBtn" onclick="saveIncome()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
        Save Income
      </button>
    </div>
  </div>
</div>

<!-- Edit Income Modal -->
<div class="modal-backdrop" id="editIncomeModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Income Entry</span>
      <button class="modal-close" data-close-modal="editIncomeModal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form id="editIncomeForm">
        <input type="hidden" name="id" id="editIncomeId">
        <div class="form-group">
          <label class="form-label">Description *</label>
          <input type="text" name="description" id="editDescription" class="form-control" required>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Amount (£) *</label>
            <div class="input-group">
              <span class="input-prefix">£</span>
              <input type="number" name="amount" id="editAmount" class="form-control" step="0.01" min="0" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="income_date" id="editDate" class="form-control" required>
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category" id="editCategory" class="form-control">
              <?php foreach ($categories as $c): ?><option><?= $c ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" id="editPayment" class="form-control">
              <option value="">— Select —</option>
              <option>Bank Transfer (BACS/CHAPS)</option>
              <option>Faster Payments</option>
              <option>Cash</option>
              <option>Cheque</option>
              <option>Direct Debit</option>
              <option>Standing Order</option>
              <option>Credit/Debit Card</option>
              <option>PayPal</option>
              <option>Other</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">🏢 Which company? (optional)</label>
          <input type="text" name="reference" id="editReference" class="form-control" placeholder="e.g. Uber, Bolt, local firm...">
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Document Type (optional)</label>
            <select name="document_type" id="editDocumentType" class="form-control">
              <option value="">— Select document type —</option>
              <option>Weekly or monthly income reports/Summary</option>
              <option>Cash job records</option>
              <option>Payslips (Employed Individuals)</option>
              <option>Rental income</option>
              <option>Benefits or grants</option>
              <option>Interest income</option>
              <option>Side business income</option>
              <option>Other Documents</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Upload File (PNG / PDF)</label>
            <input type="file" name="receipt_file" id="editFile" class="form-control" accept=".png,.pdf,image/png,application/pdf">
            <div id="editCurrentFile" style="font-size:12px;color:var(--gray-500);margin-top:4px;"></div>
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Other key documents (optional)</label>
            <select name="other_document_label" id="editOtherLabel" class="form-control">
              <option value="">— Select document —</option>
              <option>Signed 64-8 Form (authorising tax agent)</option>
              <option>HMRC Digital Authorization Approval</option>
              <option>Bank statements (business-related income)</option>
              <option>P60 / P45 form (Employed Individuals)</option>
              <option>Signed Tax Declaration Form</option>
              <option>Previous Year tax return</option>
              <option>Other Documents</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Upload File (PNG / PDF)</label>
            <input type="file" name="other_document_file" id="editOtherFile" class="form-control" accept=".png,.pdf,image/png,application/pdf">
            <div id="editCurrentOtherFile" style="font-size:12px;color:var(--gray-500);margin-top:4px;"></div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal="editIncomeModal">Cancel</button>
      <button class="btn btn-primary" onclick="updateIncome()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
        Update
      </button>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
initTableSearch('tableSearch','incomeTable');

async function saveIncome() {
  const form = document.getElementById('addIncomeForm');
  const fd = new FormData(form);
  const desc = fd.get('description'), amount = fd.get('amount'), date = fd.get('income_date');
  if (!desc || !amount || !date) { showAlert('Please fill required fields.','danger'); return; }
  const file = fd.get('receipt_file');
  if (file && file.size > 0) {
    if (!['image/png','application/pdf'].includes(file.type)) { showAlert('Only PNG and PDF files are allowed.','danger'); return; }
    if (file.size > 5 * 1024 * 1024) { showAlert('File size must be under 5MB.','danger'); return; }
  }
  fd.append('action', 'create');
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  const btn = document.getElementById('saveIncomeBtn');
  setLoading(btn, true);
  try {
    const res = await fetch('/api/income.php', { method:'POST', body: fd });
    const result = await res.json();
    if (result.success) { toast('Income added!','success'); setTimeout(()=>location.reload(),700); }
    else showAlert(result.message,'danger');
  } catch(e) { showAlert('Request failed.','danger'); }
  setLoading(btn, false);
}

async function editIncome(id) {
  const result = await apiCall('/api/income.php', { action:'get', id });
  if (result.success) {
    const r = result.data;
    document.getElementById('editIncomeId').value = r.id;
    document.getElementById('editDescription').value = r.description;
    document.getElementById('editAmount').value = r.amount;
    document.getElementById('editDate').value = r.income_date;
    document.getElementById('editCategory').value = r.category;
    document.getElementById('editPayment').value = r.payment_method || '';
    document.getElementById('editReference').value = r.reference || '';
    document.getElementById('editDocumentType').value = r.document_type || '';
    document.getElementById('editOtherLabel').value = r.other_document_label || '';
    document.getElementById('editNotes').value = r.notes || '';
    const cf = document.getElementById('editCurrentFile');
    cf.innerHTML = r.receipt_file ? `Current: <a href="/assets/uploads/income/${r.receipt_file}" target="_blank" style="color:var(--orange)">View file</a>` : '';
    const ocf = document.getElementById('editCurrentOtherFile');
    ocf.innerHTML = r.other_document_file ? `Current: <a href="/assets/uploads/income/${r.other_document_file}" target="_blank" style="color:var(--orange)">View file</a>` : '';
    openModal('editIncomeModal');
  }
}

async function updateIncome() {
  const form = document.getElementById('editIncomeForm');
  const fd = new FormData(form);
  const file = fd.get('receipt_file');
  if (file && file.size > 0) {
    if (!['image/png','application/pdf'].includes(file.type)) { toast('Only PNG and PDF files are allowed.','danger'); return; }
    if (file.size > 5 * 1024 * 1024) { toast('File size must be under 5MB.','danger'); return; }
  }
  fd.append('action', 'update');
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  try {
    const res = await fetch('/api/income.php', { method:'POST', body: fd });
    const result = await res.json();
    if (result.success) { toast('Income updated!','success'); setTimeout(()=>location.reload(),700); }
    else toast(result.message,'danger');
  } catch(e) { toast('Request failed.','danger'); }
}

async function deleteRecord(type, id) {
  if (!confirm('Delete this record? This cannot be undone.')) return;
  const result = await apiCall('/api/income.php', { action:'delete', id });
  if (result.success) { toast('Deleted!','success'); setTimeout(()=>location.reload(),600); }
  else toast(result.message,'danger');
}
</script>
</body>
</html>
