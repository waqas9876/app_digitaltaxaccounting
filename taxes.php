<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/config/config.php'; require_once __DIR__.'/includes/auth.php'; echo generateCsrf(); ?>">
  <title>Taxes — Digital Tax Accounting</title>
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
$stats    = getClientStats($clientId, $year);

// Get or create tax record for this year
$taxStmt = db()->prepare("SELECT * FROM taxes WHERE client_id=? AND tax_year=?");
$taxStmt->execute([$clientId, $year]);
$taxRecord = $taxStmt->fetch();

// All years' tax records
$allTaxStmt = db()->prepare("SELECT * FROM taxes WHERE client_id=? ORDER BY tax_year DESC");
$allTaxStmt->execute([$clientId]);
$allTaxRecords = $allTaxStmt->fetchAll();

$years = range(date('Y'), date('Y') - 5);

$statusColors = [
  'not_started' => 'badge-info',
  'in_progress' => 'badge-warning',
  'filed'       => 'badge-orange',
  'accepted'    => 'badge-success',
  'rejected'    => 'badge-danger',
];
$statusLabels = [
  'not_started' => 'Not Started',
  'in_progress' => 'In Progress',
  'filed'       => 'Filed',
  'accepted'    => 'Accepted',
  'rejected'    => 'Rejected',
];
?>

<div class="app-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <header class="top-header">
      <button class="header-menu-btn" id="menuBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="header-title">
        <h1>Taxes</h1>
        <p>Your tax filing status and history</p>
      </div>
      <div class="header-actions">
        <select class="form-control" id="yearSelector" style="width:auto;padding:8px 32px 8px 12px;font-size:13px;font-weight:600;">
          <?php foreach ($years as $y): ?><option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
        </select>
      </div>
    </header>

    <div class="page-body">

      <!-- Current Year Tax Card -->
      <div class="grid grid-2 mb-24">
        <!-- Status Card -->
        <div class="card" style="border:2px solid <?= $taxRecord ? 'var(--orange)' : 'var(--gray-200)' ?>">
          <div class="card-header">
            <span class="card-title"><?= $year ?> Tax Filing Status</span>
            <?php if ($taxRecord): ?>
              <span class="badge <?= $statusColors[$taxRecord['status']] ?>"><?= $statusLabels[$taxRecord['status']] ?></span>
            <?php else: ?>
              <span class="badge badge-info">Not Started</span>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <?php if ($taxRecord): ?>
              <div class="grid grid-2" style="gap:12px;margin-bottom:16px">
                <?php
                $fields = [
                  ['Gross Income','gross_income','var(--success)'],
                  ['Total Deductions','total_deductions','var(--orange)'],
                  ['Taxable Income','taxable_income','var(--blue)'],
                  ['Estimated Tax','estimated_tax','var(--warning)'],
                  ['Tax Paid','tax_paid','var(--info)'],
                  ['Refund/Owed','refund_owed',$taxRecord['refund_owed']>=0?'var(--success)':'var(--danger)'],
                ];
                foreach ($fields as [$label, $key, $color]):
                ?>
                  <div style="background:var(--gray-50);border-radius:10px;padding:12px 14px">
                    <div style="font-size:11px;color:var(--gray-500);margin-bottom:3px"><?= $label ?></div>
                    <div style="font-size:17px;font-weight:700;color:<?= $color ?>"><?= formatCurrency(abs((float)$taxRecord[$key])) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if ($taxRecord['notes']): ?>
                <div style="background:var(--gray-50);border-radius:10px;padding:12px 14px;margin-bottom:16px">
                  <div style="font-size:11px;color:var(--gray-500);margin-bottom:4px">Client Notes</div>
                  <div style="font-size:13px;color:var(--gray-700)"><?= nl2br(sanitize($taxRecord['notes'])) ?></div>
                </div>
              <?php endif; ?>
              <?php if ($taxRecord['admin_notes']): ?>
                <div style="background:#FFF4EE;border:1px solid #FFD5B8;border-radius:10px;padding:12px 14px;margin-bottom:16px">
                  <div style="font-size:11px;color:var(--orange);font-weight:600;margin-bottom:4px">Message from your Tax Pro</div>
                  <div style="font-size:13px;color:var(--gray-700)"><?= nl2br(sanitize($taxRecord['admin_notes'])) ?></div>
                </div>
              <?php endif; ?>
              <div style="display:flex;gap:8px">
                <button class="btn btn-primary btn-sm" data-open-modal="editTaxModal">Update Details</button>
                <a href="/summary.php?year=<?= $year ?>" class="btn btn-ghost btn-sm">View Summary</a>
              </div>
            <?php else: ?>
              <div class="empty-state" style="padding:24px 0">
                <div class="empty-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                </div>
                <h3>No tax record for <?= $year ?></h3>
                <p>Start your <?= $year ?> tax filing process</p>
                <button class="btn btn-primary" data-open-modal="editTaxModal">Start <?= $year ?> Taxes</button>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Auto-calculated from records -->
        <div class="card" style="background:linear-gradient(135deg,var(--blue) 0%,var(--blue-light) 100%);border:none">
          <div class="card-header">
            <span class="card-title" style="color:white">Auto-Calculated</span>
            <span class="badge" style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.8)"><?= $year ?></span>
          </div>
          <div class="card-body">
            <div style="color:rgba(255,255,255,.6);font-size:13px;margin-bottom:16px">Based on your income, expenses and mileage records</div>
            <?php
            $autoCalc = [
              ['Gross Income','£'.number_format($stats['income'],2),'#4ade80'],
              ['Total Expenses','£'.number_format($stats['expenses'],2),'#f87171'],
              ['Mileage Deduction','£'.number_format($stats['mile_deduction'],2),'#fb923c'],
              ['Net Profit/Loss',($stats['profit']<0?'-':'').'£'.number_format(abs($stats['profit']),2),$stats['profit']>=0?'#4ade80':'#f87171'],
              ['Est. Tax (25%)','£'.number_format(max(0,$stats['profit']*.25),2),'#fbbf24'],
            ];
            foreach ($autoCalc as [$label, $value, $color]):
            ?>
              <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08)">
                <span style="color:rgba(255,255,255,.7);font-size:14px"><?= $label ?></span>
                <span style="color:<?= $color ?>;font-weight:700;font-size:14px"><?= $value ?></span>
              </div>
            <?php endforeach; ?>
            <div style="margin-top:16px;padding:12px 14px;background:rgba(255,255,255,.1);border-radius:10px">
              <div style="font-size:11px;color:rgba(255,255,255,.5)">Disclaimer: Estimates only. Consult a tax professional for accurate figures.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tax History -->
      <div class="card mb-24">
        <div class="card-header">
          <span class="card-title">Tax Filing History</span>
          <span class="badge badge-blue"><?= count($allTaxRecords) ?> years</span>
        </div>
        <div class="table-wrap">
          <?php if (empty($allTaxRecords)): ?>
            <div class="empty-state" style="padding:40px">
              <p>No tax history yet. Start by adding your current year's tax information.</p>
            </div>
          <?php else: ?>
            <table class="data-table">
              <thead>
                <tr><th>Tax Year</th><th>Filing Status</th><th>Gross Income</th><th>Deductions</th><th>Taxable Income</th><th>Tax</th><th>Refund/Owed</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($allTaxRecords as $r): ?>
                  <tr>
                    <td style="font-weight:700;color:var(--blue)"><?= $r['tax_year'] ?></td>
                    <td style="font-size:13px;text-transform:capitalize"><?= str_replace('_',' ',$r['filing_status']) ?></td>
                    <td style="color:var(--success)"><?= formatCurrency((float)$r['gross_income']) ?></td>
                    <td style="color:var(--orange)"><?= formatCurrency((float)$r['total_deductions']) ?></td>
                    <td><?= formatCurrency((float)$r['taxable_income']) ?></td>
                    <td style="color:var(--warning)"><?= formatCurrency((float)$r['estimated_tax']) ?></td>
                    <td style="color:<?= $r['refund_owed']>=0?'var(--success)':'var(--danger)' ?>;font-weight:700">
                      <?= $r['refund_owed']>=0?'+':'' ?><?= formatCurrency((float)$r['refund_owed']) ?>
                    </td>
                    <td><span class="badge <?= $statusColors[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <!-- Important Deadlines -->
      <div class="card">
        <div class="card-header"><span class="card-title">Important Tax Deadlines <?= $year + 1 ?></span></div>
        <div class="card-body">
          <div class="grid grid-2" style="gap:12px">
            <?php
            $deadlines = [
              ['Jan 15','Q4 Estimated Tax Payment','warning'],
              ['Apr 15','Tax Day — Filing Deadline','danger'],
              ['Apr 15','Q1 Estimated Tax Payment','warning'],
              ['Jun 17','Q2 Estimated Tax Payment','warning'],
              ['Sep 16','Q3 Estimated Tax Payment','warning'],
              ['Oct 15','Extended Filing Deadline','info'],
            ];
            foreach ($deadlines as [$date, $desc, $type]):
            ?>
              <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--gray-50);border-radius:10px;border-left:3px solid var(--<?= $type ?>)">
                <div style="text-align:center;min-width:44px">
                  <div style="font-size:16px;font-weight:800;color:var(--<?= $type ?>)"><?= explode(' ',$date)[1] ?></div>
                  <div style="font-size:10px;color:var(--gray-500);text-transform:uppercase"><?= explode(' ',$date)[0] ?></div>
                </div>
                <div style="font-size:13px;font-weight:500;color:var(--blue)"><?= $desc ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit/Create Tax Modal -->
<div class="modal-backdrop" id="editTaxModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <span class="modal-title"><?= $taxRecord ? 'Update' : 'Start' ?> <?= $year ?> Tax Return</span>
      <button class="modal-close" data-close-modal="editTaxModal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form id="taxForm">
        <input type="hidden" name="tax_year" value="<?= $year ?>">
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Filing Status</label>
            <select name="filing_status" class="form-control">
              <option value="single" <?= ($taxRecord['filing_status']??'')==='single'?'selected':'' ?>>Single</option>
              <option value="married_jointly" <?= ($taxRecord['filing_status']??'')==='married_jointly'?'selected':'' ?>>Married Filing Jointly</option>
              <option value="married_separately" <?= ($taxRecord['filing_status']??'')==='married_separately'?'selected':'' ?>>Married Filing Separately</option>
              <option value="head_of_household" <?= ($taxRecord['filing_status']??'')==='head_of_household'?'selected':'' ?>>Head of Household</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach ($statusLabels as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= ($taxRecord['status']??'not_started')===$val?'selected':'' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Gross Income (£)</label>
            <div class="input-group"><span class="input-prefix">£</span>
              <input type="number" name="gross_income" class="form-control" step="0.01" value="<?= $taxRecord['gross_income'] ?? number_format($stats['income'],2,'.','') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Total Deductions (£)</label>
            <div class="input-group"><span class="input-prefix">£</span>
              <input type="number" name="total_deductions" class="form-control" step="0.01" value="<?= $taxRecord['total_deductions'] ?? number_format($stats['expenses']+$stats['mile_deduction'],2,'.','') ?>">
            </div>
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Taxable Income (£)</label>
            <div class="input-group"><span class="input-prefix">£</span>
              <input type="number" name="taxable_income" class="form-control" step="0.01" value="<?= $taxRecord['taxable_income'] ?? max(0,number_format($stats['profit'],2,'.','')) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Estimated Tax (£)</label>
            <div class="input-group"><span class="input-prefix">£</span>
              <input type="number" name="estimated_tax" class="form-control" step="0.01" value="<?= $taxRecord['estimated_tax'] ?? number_format(max(0,$stats['profit']*.25),2,'.','') ?>">
            </div>
          </div>
        </div>
        <div class="grid grid-2">
          <div class="form-group">
            <label class="form-label">Tax Already Paid (£)</label>
            <div class="input-group"><span class="input-prefix">£</span>
              <input type="number" name="tax_paid" class="form-control" step="0.01" value="<?= $taxRecord['tax_paid'] ?? '0.00' ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Refund / Amount Owed (£)</label>
            <div class="input-group"><span class="input-prefix">£</span>
              <input type="number" name="refund_owed" class="form-control" step="0.01" value="<?= $taxRecord['refund_owed'] ?? '0.00' ?>">
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes to Tax Pro</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Any specific notes or questions for your tax professional..."><?= sanitize($taxRecord['notes'] ?? '') ?></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal="editTaxModal">Cancel</button>
      <button class="btn btn-primary" id="saveTaxBtn" onclick="saveTax()">Save Tax Info</button>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
async function saveTax() {
  const data = Object.fromEntries(new FormData(document.getElementById('taxForm')));
  const btn  = document.getElementById('saveTaxBtn');
  setLoading(btn, true);
  const result = await apiCall('/api/taxes.php', { action:'save', ...data });
  setLoading(btn, false);
  if (result.success) { toast('Tax info saved!','success'); setTimeout(()=>location.reload(),700); }
  else toast(result.message,'danger');
}
</script>
</body>
</html>
