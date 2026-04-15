<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/../config/config.php'; require_once __DIR__.'/../includes/auth.php'; echo generateCsrf(); ?>">
  <title>Clients — Admin — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();
$admin = getAdmin();

// Filters
$search   = trim($_GET['q'] ?? '');
$planFilt = $_GET['plan'] ?? 'all';
$sort     = $_GET['sort'] ?? 'created_at';
$order    = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

$where  = 'WHERE is_active=1';
$params = [];
if ($search) { $where .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR business_name LIKE ?)'; $s="%$search%"; $params=[$s,$s,$s,$s]; }
if ($planFilt !== 'all') { $where .= ' AND plan=?'; $params[] = $planFilt; }

$allowed = ['first_name','last_name','email','plan','created_at','last_login'];
$sortCol = in_array($sort,$allowed) ? $sort : 'created_at';

$clients = db()->prepare("SELECT * FROM clients $where ORDER BY $sortCol $order");
$clients->execute($params);
$allClients = $clients->fetchAll();

$planColors = ['free'=>'#9AAAC0','basic'=>'#10B981','professional'=>'#FF7421','premium'=>'#F59E0B'];
?>

<div class="app-layout">
  <aside class="sidebar admin-sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="logo-icon"><img src="/assets/images/logo.png" alt="Digital Tax Accounting" ></div>
      </div>
      <button class="sidebar-close" id="sidebarClose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="sidebar-user">
      <div class="user-avatar"><?= getInitials($admin['name']) ?><span class="user-status"></span></div>
      <div class="user-info"><span class="user-name"><?= sanitize($admin['name']) ?></span><span class="plan-badge plan-premium"><?= ucfirst($admin['role']) ?></span></div>
    </div>
    <nav class="sidebar-nav"><ul>
      <li class="nav-item"><a href="/admin/dashboard.php" class="nav-link"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span><span class="nav-label">Dashboard</span></a></li>
      <li class="nav-item active"><a href="/admin/clients.php" class="nav-link"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span><span class="nav-label">Clients</span></a></li>
    </ul></nav>
    <div class="sidebar-footer">
      <a href="/admin/logout.php" class="sidebar-logout"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg><span>Log Out</span></a>
    </div>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="main-content">
    <header class="top-header admin-header">
      <button class="header-menu-btn" id="menuBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="header-title">
        <h1>Client Management</h1>
        <p><?= count($allClients) ?> client<?= count($allClients)!==1?'s':'' ?> found</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-ghost btn-sm" onclick="exportTableToCsv('clientsTable','clients.csv')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export CSV
        </button>
      </div>
    </header>

    <div class="page-body">
      <!-- Filter Bar -->
      <div class="card mb-24">
        <div class="card-body" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
          <form method="GET" style="display:flex;gap:12px;flex:1;flex-wrap:wrap">
            <div class="admin-search" style="flex:1;min-width:220px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="q" placeholder="Search by name, email, business..." value="<?= sanitize($search) ?>">
            </div>
            <select name="plan" class="form-control" style="width:auto">
              <option value="all" <?= $planFilt==='all'?'selected':'' ?>>All Plans</option>
              <option value="free" <?= $planFilt==='free'?'selected':'' ?>>Free</option>
              <option value="basic" <?= $planFilt==='basic'?'selected':'' ?>>Basic</option>
              <option value="professional" <?= $planFilt==='professional'?'selected':'' ?>>Professional</option>
              <option value="premium" <?= $planFilt==='premium'?'selected':'' ?>>Premium</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            <?php if ($search || $planFilt !== 'all'): ?><a href="/admin/clients.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
          </form>
        </div>
      </div>

      <!-- Clients Table -->
      <div class="card">
        <div class="table-wrap">
          <table class="data-table" id="clientsTable">
            <thead>
              <tr>
                <th>Client</th>
                <th>Business</th>
                <th>Plan</th>
                <th>Phone</th>
                <th>
                  <a href="?q=<?= urlencode($search) ?>&plan=<?= $planFilt ?>&sort=created_at&order=<?= $sortCol==='created_at'&&$order==='ASC'?'DESC':'ASC' ?>" style="color:inherit;text-decoration:none">
                    Joined <?= $sortCol==='created_at'?($order==='ASC'?'↑':'↓'):'' ?>
                  </a>
                </th>
                <th>Last Login</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($allClients as $c): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:10px">
                      <div class="client-avatar" style="width:36px;height:36px;font-size:12px">
                        <?php if ($c['avatar']): ?><img src="/assets/uploads/<?= sanitize($c['avatar']) ?>"><?php else: ?><?= getInitials($c['first_name'].' '.$c['last_name']) ?><?php endif; ?>
                      </div>
                      <div>
                        <div style="font-weight:600;color:var(--blue);font-size:14px"><?= sanitize($c['first_name'].' '.$c['last_name']) ?></div>
                        <div style="font-size:12px;color:var(--gray-400)"><?= sanitize($c['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td style="font-size:13px;color:var(--gray-600)"><?= sanitize($c['business_name'] ?: '—') ?></td>
                  <td>
                    <span class="badge" style="background:<?= $planColors[$c['plan']] ?>22;color:<?= $planColors[$c['plan']] ?>;font-weight:700">
                      <?= ucfirst($c['plan']) ?>
                    </span>
                  </td>
                  <td style="font-size:13px;color:var(--gray-500)"><?= sanitize($c['phone'] ?: '—') ?></td>
                  <td style="font-size:13px;color:var(--gray-500)"><?= formatDate($c['created_at']) ?></td>
                  <td style="font-size:13px;color:var(--gray-500)"><?= $c['last_login'] ? timeAgo($c['last_login']) : 'Never' ?></td>
                  <td class="text-center">
                    <span class="badge <?= $c['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span>
                  </td>
                  <td class="text-center">
                    <div style="display:flex;gap:6px;justify-content:center">
                      <a href="/admin/client-detail.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-icon" data-tooltip="View">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      </a>
                      <a href="/admin/client-edit.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-icon" data-tooltip="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </a>
                      <button class="btn btn-ghost btn-icon" style="color:<?= $c['is_active']?'var(--danger)':'var(--success)' ?>"
                              onclick="toggleClient(<?= $c['id'] ?>,<?= $c['is_active'] ?>)"
                              data-tooltip="<?= $c['is_active']?'Deactivate':'Activate' ?>">
                        <?php if ($c['is_active']): ?>
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        <?php else: ?>
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php endif; ?>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($allClients)): ?>
                <tr><td colspan="8"><div class="empty-state" style="padding:40px"><h3>No clients found</h3></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
async function toggleClient(id, isActive) {
  const action = isActive ? 'deactivate' : 'activate';
  if (!confirm(`${isActive?'Deactivate':'Activate'} this client?`)) return;
  const result = await apiCall('/api/admin.php', { action, client_id:id });
  if (result.success) { toast(result.message,'success'); setTimeout(()=>location.reload(),600); }
  else toast(result.message,'danger');
}
</script>
</body>
</html>
