<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/config/config.php'; require_once __DIR__.'/includes/auth.php'; echo generateCsrf(); ?>">
  <title>Dashboard — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireClientLogin();
$client = getClient();
$year   = (int)($_GET['year'] ?? getTaxYear());
$stats  = getClientStats((int)$_SESSION['client_id'], $year);
$monthly = getMonthlyBreakdown((int)$_SESSION['client_id'], $year);
$recentTx = getRecentTransactions((int)$_SESSION['client_id'], 8);
$expCats  = getExpenseCategories((int)$_SESSION['client_id'], $year);
$incCats  = getIncomeCategories((int)$_SESSION['client_id'], $year);

// Tax year selector
$years = range(date('Y'), date('Y') - 5);
?>

<div class="app-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <!-- Top Header -->
    <header class="top-header">
      <button class="header-menu-btn" id="menuBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="header-title">
        <h1>Dashboard</h1>
        <p>Welcome back, <?= sanitize($client['first_name']) ?>! Here's your financial overview.</p>
      </div>
      <div class="header-actions">
        <select class="form-control" id="yearSelector" style="width:auto;padding:8px 32px 8px 12px;font-size:13px;font-weight:600;">
          <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?> Tax Year</option>
          <?php endforeach; ?>
        </select>
        <button class="header-btn" onclick="window.print()" data-tooltip="Print">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        </button>
      </div>
    </header>

    <div class="page-body">
      <!-- Stat Cards -->
      <div class="grid grid-4 mb-24">
        <div class="stat-card" style="--accent:var(--success)">
          <div class="stat-icon" style="background:#ECFDF5;color:var(--success)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Income</div>
            <div class="stat-value text-success" data-animate-number="<?= $stats['income'] ?>" data-prefix="$">$0.00</div>
            <div class="stat-change up">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="18 15 12 9 6 15"/></svg>
              <?= $year ?> Year
            </div>
          </div>
        </div>

        <div class="stat-card" style="--accent:var(--danger)">
          <div class="stat-icon" style="background:#FEF2F2;color:var(--danger)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Total Expenses</div>
            <div class="stat-value text-danger" data-animate-number="<?= $stats['expenses'] ?>" data-prefix="$">$0.00</div>
            <div class="stat-change down">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg>
              <?= $year ?> Year
            </div>
          </div>
        </div>

        <div class="stat-card" style="--accent:<?= $stats['profit'] >= 0 ? 'var(--info)' : 'var(--warning)' ?>">
          <div class="stat-icon" style="background:#EFF6FF;color:var(--info)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Net Profit</div>
            <div class="stat-value <?= $stats['profit'] >= 0 ? 'text-info' : 'text-warning' ?>"
                 data-animate-number="<?= abs($stats['profit']) ?>" data-prefix="<?= $stats['profit'] < 0 ? '-$' : '$' ?>">$0.00</div>
            <div class="stat-change <?= $stats['profit'] >= 0 ? 'up' : 'down' ?>">
              <?= $stats['profit'] >= 0 ? 'Profitable' : 'Net Loss' ?>
            </div>
          </div>
        </div>

        <div class="stat-card" style="--accent:var(--orange)">
          <div class="stat-icon" style="background:#FFF4EE;color:var(--orange)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Mileage Deduction</div>
            <div class="stat-value text-orange" data-animate-number="<?= $stats['mile_deduction'] ?>" data-prefix="$">$0.00</div>
            <div class="stat-change up"><?= number_format($stats['miles'], 1) ?> miles</div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-2 mb-24">
        <!-- Monthly Chart -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Monthly Overview</span>
            <span class="badge badge-orange"><?= $year ?></span>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="monthlyChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Expense Breakdown Donut -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Expense Categories</span>
            <a href="/expenses.php" class="btn btn-ghost btn-sm">View All</a>
          </div>
          <div class="card-body">
            <?php if (empty($expCats)): ?>
              <div class="empty-state" style="padding:24px">
                <p style="color:var(--gray-400)">No expense data yet</p>
                <a href="/expenses.php" class="btn btn-primary btn-sm mt-16">Add Expense</a>
              </div>
            <?php else: ?>
              <div style="display:flex;align-items:center;gap:20px;">
                <div style="width:160px;height:160px;flex-shrink:0;">
                  <canvas id="expenseDonut"></canvas>
                </div>
                <div style="flex:1">
                  <?php foreach (array_slice($expCats, 0, 5) as $cat): ?>
                    <div style="margin-bottom:8px;">
                      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px;">
                        <span style="color:var(--gray-700);font-weight:500"><?= sanitize($cat['category']) ?></span>
                        <span style="color:var(--blue);font-weight:700"><?= formatCurrency((float)$cat['total']) ?></span>
                      </div>
                      <div class="progress">
                        <div class="progress-bar" style="width:<?= $stats['expenses'] > 0 ? round(($cat['total']/$stats['expenses'])*100) : 0 ?>%"></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Recent Transactions + Quick Actions -->
      <div class="grid grid-2 mb-24">
        <!-- Recent Transactions -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Recent Transactions</span>
            <span class="badge badge-blue"><?= count($recentTx) ?> entries</span>
          </div>
          <div class="card-body" style="padding-top:12px">
            <?php if (empty($recentTx)): ?>
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <h3>No transactions yet</h3>
                <p>Start by adding your income or expenses.</p>
                <div style="display:flex;gap:8px;justify-content:center">
                  <a href="/income.php" class="btn btn-primary btn-sm">Add Income</a>
                  <a href="/expenses.php" class="btn btn-outline btn-sm">Add Expense</a>
                </div>
              </div>
            <?php else: ?>
              <?php foreach ($recentTx as $tx): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--gray-100);">
                  <div style="width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?= $tx['type'] === 'income' ? '#ECFDF5' : '#FEF2F2' ?>;">
                    <?php if ($tx['type'] === 'income'): ?>
                      <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" width="16" height="16"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                    <?php else: ?>
                      <svg viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" width="16" height="16"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
                    <?php endif; ?>
                  </div>
                  <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:var(--blue);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= sanitize($tx['description']) ?></div>
                    <div style="font-size:11px;color:var(--gray-500)"><?= sanitize($tx['category']) ?> · <?= formatDate($tx['txn_date']) ?></div>
                  </div>
                  <div style="font-size:14px;font-weight:700;<?= $tx['type'] === 'income' ? 'color:var(--success)' : 'color:var(--danger)' ?>;white-space:nowrap">
                    <?= $tx['type'] === 'income' ? '+' : '-' ?><?= formatCurrency((float)$tx['amount']) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Actions + Tax Status -->
        <div style="display:flex;flex-direction:column;gap:20px;">
          <!-- Quick Actions -->
          <div class="card">
            <div class="card-header">
              <span class="card-title">Quick Actions</span>
            </div>
            <div class="card-body">
              <div class="grid grid-2" style="gap:10px">
                <a href="/income.php" class="btn btn-success w-full" style="padding:12px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Add Income
                </a>
                <a href="/expenses.php" class="btn btn-danger w-full" style="padding:12px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Add Expense
                </a>
                <a href="/mileage.php" class="btn btn-secondary w-full" style="padding:12px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/></svg>
                  Log Mileage
                </a>
                <a href="/notes.php" class="btn btn-ghost w-full" style="padding:12px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                  Add Note
                </a>
              </div>
            </div>
          </div>

          <!-- Tax Status -->
          <div class="card" style="background:linear-gradient(135deg,var(--blue) 0%,var(--blue-light) 100%);border:none;">
            <div class="card-body">
              <div style="color:rgba(255,255,255,.7);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">Tax Year <?= $year ?></div>
              <div style="font-size:22px;font-weight:800;color:white;margin-bottom:4px;">
                <?= formatCurrency($stats['profit']) ?>
              </div>
              <div style="color:rgba(255,255,255,.7);font-size:13px;margin-bottom:16px;">Net <?= $stats['profit'] >= 0 ? 'Profit' : 'Loss' ?> for <?= $year ?></div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:10px 14px;">
                  <div style="font-size:11px;color:rgba(255,255,255,.55);margin-bottom:2px">Deductible Expenses</div>
                  <div style="font-size:16px;font-weight:700;color:white"><?= formatCurrency($stats['expenses']) ?></div>
                </div>
                <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:10px 14px;">
                  <div style="font-size:11px;color:rgba(255,255,255,.55);margin-bottom:2px">Mileage Deduction</div>
                  <div style="font-size:16px;font-weight:700;color:white"><?= formatCurrency($stats['mile_deduction']) ?></div>
                </div>
              </div>
              <a href="/summary.php?year=<?= $year ?>" class="btn btn-primary w-full">
                View Full Summary
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div>

<script src="/assets/js/main.js"></script>
<script>
// Monthly chart
const monthlyData = <?= json_encode($monthly) ?>;
const labels  = monthlyData.map(m => m.month);
const incomes  = monthlyData.map(m => m.income);
const expenses = monthlyData.map(m => m.expenses);

new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [
      { label:'Income',  data:incomes,  backgroundColor:'rgba(16,185,129,.8)',  borderRadius:6, borderSkipped:false },
      { label:'Expenses',data:expenses, backgroundColor:'rgba(239,68,68,.7)',   borderRadius:6, borderSkipped:false }
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, boxWidth:8, font:{ size:12 } } } },
    scales:{
      y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,.06)' }, ticks:{ callback: v => '$'+v.toLocaleString() } },
      x:{ grid:{ display:false } }
    }
  }
});

// Expense donut
const cats = <?= json_encode(array_slice($expCats, 0, 6)) ?>;
if (cats.length > 0 && document.getElementById('expenseDonut')) {
  const colors = ['#FF7421','#16295A','#10B981','#3B82F6','#F59E0B','#EF4444'];
  new Chart(document.getElementById('expenseDonut'), {
    type:'doughnut',
    data:{
      labels: cats.map(c => c.category),
      datasets:[{ data: cats.map(c => c.total), backgroundColor:colors, borderWidth:0, hoverOffset:6 }]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      cutout:'72%',
      plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: ctx => ctx.label+': $'+parseFloat(ctx.raw).toFixed(2) } } }
    }
  });
}
</script>
</body>
</html>
