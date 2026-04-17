<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/config/config.php'; require_once __DIR__.'/includes/auth.php'; echo generateCsrf(); ?>">
  <title>Mileage — Digital Tax Accounting</title>
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

$stmt = db()->prepare("SELECT * FROM mileage WHERE client_id=? AND tax_year=? ORDER BY trip_date DESC");
$stmt->execute([$clientId, $year]);
$records = $stmt->fetchAll();

$totalMiles  = array_sum(array_column($records,'miles'));
$totalDeduct = array_sum(array_column($records,'deduction_amount'));
$rate        = STANDARD_MILEAGE_RATE;
$years       = range(date('Y'), date('Y') - 5);
?>

<div class="app-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <header class="top-header">
      <button class="header-menu-btn" id="menuBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="header-title">
        <h1>Mileage Tracker</h1>
        <p>HMRC standard rate: £<?= number_format($rate, 4) ?>/mile for <?= $year ?></p>
      </div>
      <div class="header-actions">
        <select class="form-control" id="yearSelector" style="width:auto;padding:8px 32px 8px 12px;font-size:13px;font-weight:600;">
          <?php foreach ($years as $y): ?><option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm" onclick="exportTableToCsv('mileTable','mileage_<?= $year ?>.csv')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export
        </button>
        <button class="btn btn-primary btn-sm" data-open-modal="addMileModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Log Trip
        </button>
      </div>
    </header>

    <div class="page-body">
      <div id="alertContainer"></div>

      <!-- Stats -->
      <div class="grid grid-3 mb-24">
        <div class="stat-card" style="--accent:var(--orange)">
          <div class="stat-icon" style="background:#FFF4EE;color:var(--orange)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Miles</div>
            <div class="stat-value text-orange"><?= number_format($totalMiles, 1) ?></div>
            <div class="stat-change up"><?= count($records) ?> trips in <?= $year ?></div>
          </div>
        </div>
        <div class="stat-card" style="--accent:var(--success)">
          <div class="stat-icon" style="background:#ECFDF5;color:var(--success)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Deduction</div>
            <div class="stat-value text-success"><?= formatCurrency($totalDeduct) ?></div>
            <div class="stat-change up">at £<?= $rate ?>/mile</div>
          </div>
        </div>
        <div class="stat-card" style="--accent:var(--info)">
          <div class="stat-icon" style="background:#EFF6FF;color:var(--info)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Avg. Miles/Trip</div>
            <div class="stat-value" style="color:var(--info)"><?= count($records) > 0 ? number_format($totalMiles/count($records),1) : '0.0' ?></div>
            <div class="stat-change up">per trip average</div>
          </div>
        </div>
      </div>

      <!-- Mileage Rate Banner -->
      <div class="card mb-24" style="background:linear-gradient(135deg,var(--blue) 0%,var(--blue-light) 100%);border:none">
        <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
          <div>
            <svg viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.6)" stroke-width="2" width="40" height="40"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div style="flex:1">
            <div style="color:white;font-size:15px;font-weight:700;margin-bottom:4px">HMRC Standard Mileage Rate <?= $year ?></div>
            <div style="color:rgba(255,255,255,.75);font-size:13px">The HMRC standard mileage rate for business use is <strong style="color:#FF7421">£<?= $rate ?> per mile</strong>. This rate is automatically applied to all your trips.</div>
          </div>
          <div style="text-align:center;background:rgba(255,255,255,.1);border-radius:12px;padding:14px 24px">
            <div style="font-size:28px;font-weight:800;color:#FF7421">£<?= $rate ?></div>
            <div style="font-size:12px;color:rgba(255,255,255,.6)">per mile</div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card">
        <div class="card-header" style="padding:20px 24px">
          <span class="card-title">Trip Log</span>
          <div class="table-search" style="min-width:220px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="tableSearch" placeholder="Search trips...">
          </div>
        </div>
        <div class="table-wrap">
          <?php if (empty($records)): ?>
            <div class="empty-state" style="padding:60px">
              <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/></svg></div>
              <h3>No mileage logged</h3>
              <p>Start logging your business trips to maximize deductions</p>
              <button class="btn btn-primary" data-open-modal="addMileModal">Log First Trip</button>
            </div>
          <?php else: ?>
            <table class="data-table" id="mileTable">
              <thead>
                <tr>
                  <th>Date</th><th>Purpose</th><th>From</th><th>To</th><th>Vehicle</th>
                  <th class="text-right">Miles</th><th class="text-right">Deduction</th><th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($records as $r): ?>
                  <tr>
                    <td style="white-space:nowrap;color:var(--gray-500);font-size:13px"><?= formatDate($r['trip_date']) ?></td>
                    <td style="font-weight:600;color:var(--blue)"><?= sanitize($r['purpose']) ?></td>
                    <td style="font-size:13px;color:var(--gray-600)"><?= sanitize($r['from_location'] ?: '—') ?></td>
                    <td style="font-size:13px;color:var(--gray-600)"><?= sanitize($r['to_location'] ?: '—') ?></td>
                    <td><span class="badge badge-info"><?= sanitize($r['vehicle'] ?: 'N/A') ?></span></td>
                    <td class="text-right"><span style="font-weight:700;color:var(--orange)"><?= number_format((float)$r['miles'],1) ?></span></td>
                    <td class="text-right"><span style="font-weight:700;color:var(--success)"><?= formatCurrency((float)$r['deduction_amount']) ?></span></td>
                    <td class="text-center">
                      <button class="btn btn-ghost btn-icon" style="color:var(--danger)" onclick="deleteMile(<?= $r['id'] ?>)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr style="background:var(--gray-50)">
                  <td colspan="5" style="padding:14px 16px;font-weight:700;color:var(--blue)">Totals</td>
                  <td class="text-right" style="padding:14px 16px;font-weight:800;color:var(--orange)"><?= number_format($totalMiles,1) ?> mi</td>
                  <td class="text-right" style="padding:14px 16px;font-weight:800;color:var(--success)"><?= formatCurrency($totalDeduct) ?></td>
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

<!-- Add Mileage Modal -->
<div class="modal-backdrop" id="addMileModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Log Business Trip</span>
      <button class="modal-close" data-close-modal="addMileModal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form id="addMileForm">
        <div class="form-group">
          <label class="form-label">Trip Purpose *</label>
          <input type="text" name="purpose" class="form-control" placeholder="e.g. Client meeting, Supply pickup" required>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Trip Date *</label>
            <input type="date" name="trip_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Miles *</label>
            <input type="number" name="miles" id="milesInput" class="form-control" placeholder="0.0" step="0.1" min="0" required oninput="calcDeduction()">
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">From Location</label>
            <input type="text" name="from_location" class="form-control" placeholder="Starting address">
          </div>
          <div class="form-group">
            <label class="form-label">To Location</label>
            <input type="text" name="to_location" class="form-control" placeholder="Destination address">
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Vehicle</label>
            <input type="text" name="vehicle" class="form-control" placeholder="e.g. 2022 Toyota Camry">
          </div>
          <div class="form-group">
            <label class="form-label">Tax Year</label>
            <select name="tax_year" class="form-control">
              <?php foreach ($years as $y): ?><option <?= $y===$year?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <div style="background:var(--gray-50);border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center">
            <div>
              <div style="font-size:12px;color:var(--gray-500);margin-bottom:2px">Estimated Deduction</div>
              <div style="font-size:20px;font-weight:800;color:var(--success)" id="deductionPreview">£0.00</div>
            </div>
            <div style="font-size:12px;color:var(--gray-400)">at £<?= $rate ?>/mile</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal="addMileModal">Cancel</button>
      <button class="btn btn-primary" id="saveMileBtn" onclick="saveMileage()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/></svg>
        Save Trip
      </button>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
initTableSearch('tableSearch','mileTable');
const RATE = <?= $rate ?>;

function calcDeduction() {
  const miles = parseFloat(document.getElementById('milesInput').value) || 0;
  document.getElementById('deductionPreview').textContent = '£' + (miles * RATE).toFixed(2);
}

async function saveMileage() {
  const form = document.getElementById('addMileForm');
  const data = Object.fromEntries(new FormData(form));
  if (!data.purpose || !data.miles) { showAlert('Fill required fields.','danger'); return; }
  const btn = document.getElementById('saveMileBtn');
  setLoading(btn, true);
  const result = await apiCall('/api/mileage.php', { action:'create', ...data });
  setLoading(btn, false);
  if (result.success) { toast('Trip logged!','success'); setTimeout(()=>location.reload(),700); }
  else showAlert(result.message,'danger');
}

async function deleteMile(id) {
  if (!confirm('Delete this trip?')) return;
  const result = await apiCall('/api/mileage.php', { action:'delete', id });
  if (result.success) { toast('Deleted!','success'); setTimeout(()=>location.reload(),600); }
  else toast(result.message,'danger');
}
</script>
</body>
</html>
