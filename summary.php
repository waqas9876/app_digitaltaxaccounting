<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Summary — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    @media print {
      .sidebar,.top-header,.no-print { display:none!important; }
      .main-content { margin-left:0!important; }
      .page-body { padding:0!important; }
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

$stats   = getClientStats($clientId, $year);
$monthly = getMonthlyBreakdown($clientId, $year);
$expCats = getExpenseCategories($clientId, $year);
$incCats = getIncomeCategories($clientId, $year);

// Top income entries
$topInc = db()->prepare("SELECT description,amount,income_date,category FROM income WHERE client_id=? AND tax_year=? ORDER BY amount DESC LIMIT 10");
$topInc->execute([$clientId, $year]);
$topIncomes = $topInc->fetchAll();

// Top expense entries
$topExp = db()->prepare("SELECT description,amount,expense_date,category FROM expenses WHERE client_id=? AND tax_year=? ORDER BY amount DESC LIMIT 10");
$topExp->execute([$clientId, $year]);
$topExpenses = $topExp->fetchAll();

$years = range(date('Y'), date('Y') - 5);
$estimatedTax = max(0, $stats['profit'] * 0.25); // simplified estimate
?>

<div class="app-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <header class="top-header no-print">
      <button class="header-menu-btn" id="menuBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="header-title">
        <h1>Financial Summary</h1>
        <p>Year-end overview for <?= $year ?></p>
      </div>
      <div class="header-actions">
        <select class="form-control" id="yearSelector" style="width:auto;padding:8px 32px 8px 12px;font-size:13px;font-weight:600;">
          <?php foreach ($years as $y): ?><option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm no-print" onclick="window.print()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print Report
        </button>
      </div>
    </header>

    <div class="page-body">
      <!-- Report Header (print-visible) -->
      <div style="text-align:center;margin-bottom:28px;display:none" class="print-header">
        <h2 style="font-size:24px;color:var(--blue)">Digital Tax Accounting — Annual Summary</h2>
        <p style="color:var(--gray-500)">Tax Year <?= $year ?> · <?= sanitize($client['first_name'].' '.$client['last_name']) ?></p>
      </div>

      <!-- Profit/Loss Banner -->
      <div class="card mb-24" style="background:linear-gradient(135deg,<?= $stats['profit'] >= 0 ? 'var(--blue),var(--blue-light)' : '#7f1d1d,#991b1b' ?>);border:none;overflow:visible">
        <div class="card-body" style="display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center">
          <div>
            <div style="color:rgba(255,255,255,.7);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px">
              <?= $year ?> Net <?= $stats['profit'] >= 0 ? 'Profit' : 'Loss' ?> Summary
            </div>
            <div style="font-size:42px;font-weight:900;color:white;line-height:1;margin-bottom:8px">
              <?= $stats['profit'] < 0 ? '-' : '' ?><?= formatCurrency(abs($stats['profit'])) ?>
            </div>
            <div style="color:rgba(255,255,255,.7);font-size:14px">
              Income <?= formatCurrency($stats['income']) ?> &minus; Expenses <?= formatCurrency($stats['expenses']) ?>
            </div>
          </div>
          <div style="display:grid;gap:10px">
            <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:12px 18px;text-align:center">
              <div style="font-size:10px;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.8px">Est. Tax (25%)</div>
              <div style="font-size:18px;font-weight:800;color:#FF7421"><?= formatCurrency($estimatedTax) ?></div>
            </div>
            <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:12px 18px;text-align:center">
              <div style="font-size:10px;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.8px">Mileage Savings</div>
              <div style="font-size:18px;font-weight:800;color:#34d399"><?= formatCurrency($stats['mile_deduction']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- 4 Key Numbers -->
      <div class="grid grid-4 mb-24">
        <?php
        $summaryStats = [
          ['label'=>'Gross Income','value'=>$stats['income'],'color'=>'var(--success)','bg'=>'#ECFDF5'],
          ['label'=>'Total Expenses','value'=>$stats['expenses'],'color'=>'var(--danger)','bg'=>'#FEF2F2'],
          ['label'=>'Mileage Deduction','value'=>$stats['mile_deduction'],'color'=>'var(--orange)','bg'=>'#FFF4EE'],
          ['label'=>'Net Profit/Loss','value'=>$stats['profit'],'color'=>$stats['profit']>=0?'var(--info)':'var(--warning)','bg'=>'#EFF6FF'],
        ];
        foreach ($summaryStats as $s):
        ?>
          <div class="card" style="border-top:3px solid <?= $s['color'] ?>">
            <div class="card-body" style="text-align:center;padding:20px">
              <div style="font-size:12px;color:var(--gray-500);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px"><?= $s['label'] ?></div>
              <div style="font-size:24px;font-weight:800;color:<?= $s['color'] ?>"><?= formatCurrency(abs($s['value'])) ?></div>
              <?php if ($s['label'] === 'Net Profit/Loss'): ?>
                <div style="font-size:12px;margin-top:4px;color:var(--gray-400)"><?= $s['value'] >= 0 ? 'Profit' : 'Loss' ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Charts -->
      <div class="grid grid-2 mb-24">
        <div class="card">
          <div class="card-header"><span class="card-title">Monthly Income vs Expenses</span></div>
          <div class="card-body"><div class="chart-container"><canvas id="monthlyChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">Expense Breakdown</span></div>
          <div class="card-body">
            <div style="height:220px"><canvas id="expDonut"></canvas></div>
          </div>
        </div>
      </div>

      <!-- Top Income & Expenses tables -->
      <div class="grid grid-2 mb-24">
        <div class="card">
          <div class="card-header"><span class="card-title">Top Income Sources</span></div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>Description</th><th>Category</th><th class="text-right">Amount</th></tr></thead>
              <tbody>
                <?php foreach ($topIncomes as $r): ?>
                  <tr>
                    <td style="font-size:13px"><?= sanitize(substr($r['description'],0,40)) ?></td>
                    <td><span class="badge badge-success"><?= sanitize($r['category']) ?></span></td>
                    <td class="text-right" style="font-weight:700;color:var(--success)"><?= formatCurrency((float)$r['amount']) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($topIncomes)): ?><tr><td colspan="3" class="text-center" style="padding:20px;color:var(--gray-400)">No income recorded</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">Top Expenses</span></div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>Description</th><th>Category</th><th class="text-right">Amount</th></tr></thead>
              <tbody>
                <?php foreach ($topExpenses as $r): ?>
                  <tr>
                    <td style="font-size:13px"><?= sanitize(substr($r['description'],0,40)) ?></td>
                    <td><span class="badge badge-warning"><?= sanitize($r['category']) ?></span></td>
                    <td class="text-right" style="font-weight:700;color:var(--danger)"><?= formatCurrency((float)$r['amount']) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($topExpenses)): ?><tr><td colspan="3" class="text-center" style="padding:20px;color:var(--gray-400)">No expenses recorded</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Income Categories breakdown -->
      <?php if (!empty($incCats)): ?>
      <div class="card mb-24">
        <div class="card-header"><span class="card-title">Income by Category</span></div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
            <?php foreach ($incCats as $cat): ?>
              <div style="background:var(--gray-50);border-radius:10px;padding:14px 16px">
                <div style="font-size:12px;color:var(--gray-500);margin-bottom:4px"><?= sanitize($cat['category']) ?></div>
                <div style="font-size:18px;font-weight:700;color:var(--success)"><?= formatCurrency((float)$cat['total']) ?></div>
                <div class="progress" style="margin-top:8px">
                  <div class="progress-bar" style="background:var(--success);width:<?= $stats['income']>0?round(($cat['total']/$stats['income'])*100):0 ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Tax Notice -->
      <div class="alert alert-warning no-print">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span><strong>Disclaimer:</strong> The estimated tax figure above is for informational purposes only. Please consult with a tax professional for your actual tax liability.</span>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
const monthly = <?= json_encode($monthly) ?>;
new Chart(document.getElementById('monthlyChart'), {
  type:'bar',
  data:{
    labels: monthly.map(m=>m.month),
    datasets:[
      {label:'Income', data:monthly.map(m=>m.income), backgroundColor:'rgba(16,185,129,.8)', borderRadius:5, borderSkipped:false},
      {label:'Expenses',data:monthly.map(m=>m.expenses),backgroundColor:'rgba(239,68,68,.7)',borderRadius:5,borderSkipped:false}
    ]
  },
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8}}},
    scales:{y:{beginAtZero:true,ticks:{callback:v=>'$'+v.toLocaleString()}},x:{grid:{display:false}}}
  }
});

const cats = <?= json_encode($expCats) ?>;
if (cats.length && document.getElementById('expDonut')) {
  const colors = ['#FF7421','#16295A','#10B981','#3B82F6','#F59E0B','#EF4444','#8B5CF6','#EC4899'];
  new Chart(document.getElementById('expDonut'),{
    type:'doughnut',
    data:{
      labels:cats.map(c=>c.category),
      datasets:[{data:cats.map(c=>c.total),backgroundColor:colors,borderWidth:0,hoverOffset:8}]
    },
    options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'right',labels:{font:{size:11}}}}}
  });
}
</script>
</body>
</html>
