<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/../config/config.php'; require_once __DIR__.'/../includes/auth.php'; echo generateCsrf(); ?>">
  <title>Admin Dashboard — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();
$admin = getAdmin();

// Stats
$totalClients = db()->query("SELECT COUNT(*) FROM clients WHERE is_active=1")->fetchColumn();
$newThisMonth = db()->query("SELECT COUNT(*) FROM clients WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$totalIncome  = db()->query("SELECT COALESCE(SUM(amount),0) FROM income WHERE tax_year=".getTaxYear())->fetchColumn();
$totalExp     = db()->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE tax_year=".getTaxYear())->fetchColumn();
$planBreakdown = db()->query("SELECT plan, COUNT(*) cnt FROM clients WHERE is_active=1 GROUP BY plan")->fetchAll();

// Recent clients
$recentClients = db()->query("SELECT * FROM clients ORDER BY created_at DESC LIMIT 8")->fetchAll();

// Monthly signup trend
$monthlySignups = [];
for ($m=1;$m<=12;$m++) {
    $cnt = db()->prepare("SELECT COUNT(*) FROM clients WHERE MONTH(created_at)=? AND YEAR(created_at)=?");
    $cnt->execute([$m, date('Y')]);
    $monthlySignups[] = ['month'=>date('M',mktime(0,0,0,$m,1)),'count'=>(int)$cnt->fetchColumn()];
}
?>

<div class="app-layout">
  <!-- Admin Sidebar -->
  <aside class="sidebar admin-sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="logo-icon"><svg viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="#FF7421"/><path d="M12 28L20 12L28 28H12Z" fill="#fff" opacity=".9"/></svg></div>
        <div class="logo-text"><span class="logo-name">Admin Panel</span><span class="logo-sub">Digital Tax</span></div>
      </div>
      <button class="sidebar-close" id="sidebarClose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>

    <div class="sidebar-user">
      <div class="user-avatar" style="background:var(--orange)"><?= getInitials($admin['name']) ?><span class="user-status"></span></div>
      <div class="user-info"><span class="user-name"><?= sanitize($admin['name']) ?></span><span class="plan-badge plan-premium"><?= ucfirst($admin['role']) ?></span></div>
    </div>

    <nav class="sidebar-nav">
      <ul>
        <?php
        $adminNav = [
          ['/admin/dashboard.php','grid','Dashboard'],
          ['/admin/clients.php','users','Clients'],
          ['/admin/dashboard.php#income','trending-up','Income Overview'],
          ['/admin/dashboard.php#taxes','percent','Tax Records'],
        ];
        $svgPaths = [
          'grid'       => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
          'users'      => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
          'trending-up'=> '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
          'percent'    => '<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        ];
        $currentUrl = $_SERVER['PHP_SELF'];
        foreach ($adminNav as [$url, $icon, $label]):
          $active = $currentUrl === $url || (strpos($url,'#') !== false && $currentUrl === strtok($url,'#'));
        ?>
          <li class="nav-item <?= $active?'active':'' ?>">
            <a href="<?= $url ?>" class="nav-link">
              <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><<?= $svgPaths[$icon] ?>></svg></span>
              <span class="nav-label"><?= $label ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="sidebar-footer">
      <a href="/" class="sidebar-ext-link" target="_blank">
        <span style="font-size:13px">← View Client Portal</span>
      </a>
      <a href="/admin/logout.php" class="sidebar-logout">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        <span>Log Out</span>
      </a>
    </div>
  </aside>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="main-content">
    <header class="top-header admin-header">
      <button class="header-menu-btn" id="menuBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="header-title">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?= sanitize($admin['name']) ?></p>
      </div>
      <div class="header-actions">
        <a href="/admin/clients.php" class="btn btn-primary btn-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          Manage Clients
        </a>
      </div>
    </header>

    <div class="page-body">
      <!-- Stats Row -->
      <div class="grid grid-4 mb-24">
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:#EFF6FF;color:#3B82F6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
          <div class="admin-stat-info"><div class="admin-stat-num"><?= number_format($totalClients) ?></div><div class="admin-stat-lbl">Total Clients</div></div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:#ECFDF5;color:#10B981"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div>
          <div class="admin-stat-info"><div class="admin-stat-num"><?= number_format($newThisMonth) ?></div><div class="admin-stat-lbl">New This Month</div></div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:#ECFDF5;color:#10B981"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg></div>
          <div class="admin-stat-info"><div class="admin-stat-num"><?= formatCurrency((float)$totalIncome) ?></div><div class="admin-stat-lbl">Total Income <?= getTaxYear() ?></div></div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:#FEF2F2;color:#EF4444"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg></div>
          <div class="admin-stat-info"><div class="admin-stat-num"><?= formatCurrency((float)$totalExp) ?></div><div class="admin-stat-lbl">Total Expenses <?= getTaxYear() ?></div></div>
        </div>
      </div>

      <!-- Charts + Recent -->
      <div class="grid grid-2 mb-24">
        <!-- Signup Trend -->
        <div class="card">
          <div class="card-header"><span class="card-title">Client Signups — <?= date('Y') ?></span></div>
          <div class="card-body"><div class="chart-container"><canvas id="signupChart"></canvas></div></div>
        </div>

        <!-- Plan Breakdown -->
        <div class="card">
          <div class="card-header"><span class="card-title">Plan Distribution</span></div>
          <div class="card-body">
            <?php
            $planColors = ['free'=>'#9AAAC0','basic'=>'#10B981','professional'=>'#FF7421','premium'=>'#F59E0B'];
            $planLabels = ['free'=>'Free','basic'=>'Basic','professional'=>'Professional','premium'=>'Premium'];
            foreach ($planBreakdown as $row):
              $pct = $totalClients > 0 ? round(($row['cnt']/$totalClients)*100) : 0;
              $color = $planColors[$row['plan']] ?? '#9AAAC0';
            ?>
              <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                  <span style="font-size:13px;font-weight:600;color:var(--blue)"><?= $planLabels[$row['plan']] ?? ucfirst($row['plan']) ?></span>
                  <span style="font-size:13px;color:var(--gray-500)"><?= $row['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" style="background:<?= $color ?>;width:<?= $pct ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Recent Clients -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Recent Clients</span>
          <a href="/admin/clients.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding-top:8px">
          <?php foreach ($recentClients as $c): ?>
            <div class="client-row">
              <div class="client-avatar">
                <?php if ($c['avatar']): ?>
                  <img src="/assets/uploads/<?= sanitize($c['avatar']) ?>" alt="">
                <?php else: ?>
                  <?= getInitials($c['first_name'].' '.$c['last_name']) ?>
                <?php endif; ?>
              </div>
              <div style="flex:1;min-width:0">
                <div class="client-name"><?= sanitize($c['first_name'].' '.$c['last_name']) ?></div>
                <div class="client-email"><?= sanitize($c['email']) ?></div>
              </div>
              <?php if ($c['business_name']): ?>
                <div style="font-size:12px;color:var(--gray-500);display:none"><?= sanitize($c['business_name']) ?></div>
              <?php endif; ?>
              <div class="client-meta">
                <div class="client-plan" style="color:<?= $planColors[$c['plan']] ?? '#9AAAC0' ?>;font-weight:700"><?= ucfirst($c['plan']) ?></div>
                <div class="client-joined"><?= formatDate($c['created_at']) ?></div>
              </div>
              <a href="/admin/client-detail.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm" style="margin-left:8px">View</a>
            </div>
          <?php endforeach; ?>
          <?php if (empty($recentClients)): ?>
            <div class="empty-state" style="padding:40px"><p>No clients registered yet.</p></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
const signups = <?= json_encode($monthlySignups) ?>;
new Chart(document.getElementById('signupChart'),{
  type:'line',
  data:{
    labels:signups.map(s=>s.month),
    datasets:[{label:'New Clients',data:signups.map(s=>s.count),borderColor:'#FF7421',backgroundColor:'rgba(255,116,33,.1)',fill:true,tension:.4,borderWidth:2,pointBackgroundColor:'#FF7421',pointRadius:4}]
  },
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false}},
    scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}}
  }
});
</script>
</body>
</html>
