<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/../config/config.php'; require_once __DIR__.'/../includes/auth.php'; echo generateCsrf(); ?>">
  <title>Client Detail — Admin</title>
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
$admin    = getAdmin();
$clientId = (int)($_GET['id'] ?? 0);
if (!$clientId) { header('Location: /admin/clients.php'); exit; }

$cStmt = db()->prepare('SELECT * FROM clients WHERE id=?');
$cStmt->execute([$clientId]);
$client = $cStmt->fetch();
if (!$client) { header('Location: /admin/clients.php'); exit; }

$year  = (int)($_GET['year'] ?? getTaxYear());
$stats = getClientStats($clientId, $year);
$monthly = getMonthlyBreakdown($clientId, $year);
$recentTx = getRecentTransactions($clientId, 10);

// Admin notes for this client
$notesStmt = db()->prepare("SELECT acn.*, a.name admin_name FROM admin_client_notes acn JOIN admins a ON acn.admin_id=a.id WHERE acn.client_id=? ORDER BY acn.created_at DESC");
$notesStmt->execute([$clientId]);
$adminNotes = $notesStmt->fetchAll();

// Tax records
$taxStmt = db()->prepare("SELECT * FROM taxes WHERE client_id=? ORDER BY tax_year DESC");
$taxStmt->execute([$clientId]);
$taxRecords = $taxStmt->fetchAll();

$planColors = ['free'=>'#9AAAC0','basic'=>'#10B981','professional'=>'#FF7421','premium'=>'#F59E0B'];
$years = range(date('Y'), date('Y') - 5);
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
      <li class="nav-item active"><a href="/admin/clients.php" class="nav-link"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span><span class="nav-label">Clients</span></a></li>
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
        <div style="display:flex;align-items:center;gap:10px">
          <a href="/admin/clients.php" style="color:var(--gray-500);font-size:13px">Clients</a>
          <span style="color:var(--gray-300)">/</span>
          <h1 style="font-size:18px"><?= sanitize($client['first_name'].' '.$client['last_name']) ?></h1>
        </div>
      </div>
      <div class="header-actions">
        <select class="form-control" id="yearSelector" style="width:auto;padding:8px 32px 8px 12px;font-size:13px;font-weight:600;">
          <?php foreach ($years as $y): ?><option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
        </select>
        <a href="/admin/client-edit.php?id=<?= $clientId ?>" class="btn btn-primary btn-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit Client
        </a>
      </div>
    </header>

    <div class="page-body">
      <!-- Client Profile Card -->
      <div class="card mb-24">
        <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
          <div style="width:64px;height:64px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:white;overflow:hidden;flex-shrink:0">
            <?php if ($client['avatar']): ?><img src="/assets/uploads/<?= sanitize($client['avatar']) ?>" style="width:100%;height:100%;object-fit:cover"><?php else: ?><?= getInitials($client['first_name'].' '.$client['last_name']) ?><?php endif; ?>
          </div>
          <div style="flex:1;min-width:200px">
            <div style="font-size:20px;font-weight:800;color:var(--blue)"><?= sanitize($client['first_name'].' '.$client['last_name']) ?></div>
            <div style="font-size:14px;color:var(--gray-500)"><?= sanitize($client['email']) ?></div>
            <?php if ($client['business_name']): ?>
              <div style="font-size:13px;color:var(--gray-400)">🏢 <?= sanitize($client['business_name']) ?> · <?= sanitize($client['business_type'] ?? '') ?></div>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <div style="text-align:center;padding:10px 16px;background:var(--gray-50);border-radius:10px">
              <div style="font-size:18px;font-weight:800;color:var(--blue)"><?= formatCurrency($stats['income']) ?></div>
              <div style="font-size:11px;color:var(--gray-500)">Income <?= $year ?></div>
            </div>
            <div style="text-align:center;padding:10px 16px;background:var(--gray-50);border-radius:10px">
              <div style="font-size:18px;font-weight:800;color:var(--danger)"><?= formatCurrency($stats['expenses']) ?></div>
              <div style="font-size:11px;color:var(--gray-500)">Expenses <?= $year ?></div>
            </div>
            <div style="text-align:center;padding:10px 16px;background:var(--gray-50);border-radius:10px">
              <div style="font-size:18px;font-weight:800;color:<?= $stats['profit']>=0?'var(--success)':'var(--warning)' ?>"><?= formatCurrency($stats['profit']) ?></div>
              <div style="font-size:11px;color:var(--gray-500)">Net Profit</div>
            </div>
            <div style="text-align:center;padding:10px 16px;background:<?= $planColors[$client['plan']] ?>22;border-radius:10px">
              <div style="font-size:18px;font-weight:800;color:<?= $planColors[$client['plan']] ?>"><?= ucfirst($client['plan']) ?></div>
              <div style="font-size:11px;color:var(--gray-500)">Current Plan</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-2 mb-24">
        <div class="card">
          <div class="card-header"><span class="card-title">Monthly Overview <?= $year ?></span></div>
          <div class="card-body"><div class="chart-container"><canvas id="clientChart"></canvas></div></div>
        </div>
        <!-- Tax Records -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Tax Filing History</span>
            <a href="/admin/client-edit.php?id=<?= $clientId ?>#taxes" class="btn btn-ghost btn-sm">Update</a>
          </div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>Year</th><th>Status</th><th>Income</th><th>Tax</th><th>Refund/Owed</th></tr></thead>
              <tbody>
                <?php
                $statusColors = ['not_started'=>'badge-info','in_progress'=>'badge-warning','filed'=>'badge-orange','accepted'=>'badge-success','rejected'=>'badge-danger'];
                $statusLabels = ['not_started'=>'Not Started','in_progress'=>'In Progress','filed'=>'Filed','accepted'=>'Accepted','rejected'=>'Rejected'];
                foreach ($taxRecords as $tr):
                ?>
                  <tr>
                    <td style="font-weight:700"><?= $tr['tax_year'] ?></td>
                    <td><span class="badge <?= $statusColors[$tr['status']] ?>"><?= $statusLabels[$tr['status']] ?></span></td>
                    <td style="color:var(--success)"><?= formatCurrency((float)$tr['gross_income']) ?></td>
                    <td style="color:var(--warning)"><?= formatCurrency((float)$tr['estimated_tax']) ?></td>
                    <td style="color:<?= $tr['refund_owed']>=0?'var(--success)':'var(--danger)' ?>;font-weight:600"><?= $tr['refund_owed']>=0?'+':'' ?><?= formatCurrency((float)$tr['refund_owed']) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($taxRecords)): ?><tr><td colspan="5" class="text-center" style="padding:20px;color:var(--gray-400)">No tax records</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Recent Transactions + Admin Notes -->
      <div class="grid grid-2 mb-24">
        <!-- Recent Tx -->
        <div class="card">
          <div class="card-header"><span class="card-title">Recent Transactions</span></div>
          <div class="card-body" style="padding-top:8px">
            <?php foreach ($recentTx as $tx): ?>
              <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--gray-100)">
                <div style="width:32px;height:32px;border-radius:8px;background:<?= $tx['type']==='income'?'#ECFDF5':'#FEF2F2' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <?php if ($tx['type']==='income'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" width="14" height="14"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                  <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" width="14" height="14"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
                  <?php endif; ?>
                </div>
                <div style="flex:1;min-width:0">
                  <div style="font-size:13px;font-weight:600;color:var(--blue);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= sanitize($tx['description']) ?></div>
                  <div style="font-size:11px;color:var(--gray-400)"><?= formatDate($tx['txn_date']) ?></div>
                </div>
                <div style="font-weight:700;font-size:13px;<?= $tx['type']==='income'?'color:var(--success)':'color:var(--danger)' ?>">
                  <?= $tx['type']==='income'?'+':'-' ?><?= formatCurrency((float)$tx['amount']) ?>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($recentTx)): ?><div class="empty-state" style="padding:24px"><p>No transactions</p></div><?php endif; ?>
          </div>
        </div>

        <!-- Admin Notes -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Admin Notes</span>
            <button class="btn btn-primary btn-sm" data-open-modal="addAdminNoteModal">Add Note</button>
          </div>
          <div class="card-body" style="padding-top:8px">
            <?php foreach ($adminNotes as $note): ?>
              <div class="activity-item">
                <div class="activity-dot" style="background:#FFF4EE;color:#FF7421">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                </div>
                <div class="activity-content">
                  <div class="activity-title"><?= nl2br(sanitize($note['note'])) ?></div>
                  <div class="activity-time"><?= sanitize($note['admin_name']) ?> · <?= timeAgo($note['created_at']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($adminNotes)): ?><div style="color:var(--gray-400);font-size:13px;text-align:center;padding:20px">No admin notes yet</div><?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Client Info Details -->
      <div class="card">
        <div class="card-header"><span class="card-title">Client Details</span></div>
        <div class="card-body">
          <div class="grid grid-3" style="gap:12px">
            <?php
            $details = [
              ['Phone',$client['phone']??'—'],
              ['Address',$client['address']??'—'],
              ['City',$client['city']??'—'],
              ['State',$client['state']??'—'],
              ['ZIP',$client['zip_code']??'—'],
              ['Member Since',formatDate($client['created_at'])],
              ['Last Login',$client['last_login']?formatDate($client['last_login']):'Never'],
              ['Email Verified',$client['email_verified']?'Yes':'No'],
              ['Account Status',$client['is_active']?'Active':'Inactive'],
            ];
            foreach ($details as [$label,$val]):
            ?>
              <div style="padding:12px 14px;background:var(--gray-50);border-radius:10px">
                <div style="font-size:11px;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px"><?= $label ?></div>
                <div style="font-size:14px;font-weight:600;color:var(--blue)"><?= sanitize($val) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Admin Note Modal -->
<div class="modal-backdrop" id="addAdminNoteModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Admin Note</span>
      <button class="modal-close" data-close-modal="addAdminNoteModal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="modal-body">
      <form id="adminNoteForm">
        <div class="form-group">
          <label class="form-label">Note</label>
          <textarea name="note" class="form-control" rows="4" placeholder="Internal note about this client..." required></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal="addAdminNoteModal">Cancel</button>
      <button class="btn btn-primary" id="saveNoteBtn" onclick="saveAdminNote()">Save Note</button>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script>
const monthly = <?= json_encode($monthly) ?>;
new Chart(document.getElementById('clientChart'),{
  type:'bar',
  data:{
    labels:monthly.map(m=>m.month),
    datasets:[
      {label:'Income',data:monthly.map(m=>m.income),backgroundColor:'rgba(16,185,129,.8)',borderRadius:5},
      {label:'Expenses',data:monthly.map(m=>m.expenses),backgroundColor:'rgba(239,68,68,.7)',borderRadius:5}
    ]
  },
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8}}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'$'+v.toLocaleString()}},x:{grid:{display:false}}}}
});

async function saveAdminNote() {
  const data = Object.fromEntries(new FormData(document.getElementById('adminNoteForm')));
  data.client_id = <?= $clientId ?>;
  const btn = document.getElementById('saveNoteBtn');
  setLoading(btn, true);
  const result = await apiCall('/api/admin.php', { action:'add_note', ...data });
  setLoading(btn, false);
  if (result.success) { toast('Note added!','success'); setTimeout(()=>location.reload(),700); }
  else toast(result.message,'danger');
}
</script>
</body>
</html>
