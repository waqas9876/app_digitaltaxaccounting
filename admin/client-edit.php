<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php require_once __DIR__.'/../config/config.php'; require_once __DIR__.'/../includes/auth.php'; echo generateCsrf(); ?>">
  <title>Edit Client — Admin</title>
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
$admin    = getAdmin();
$clientId = (int)($_GET['id'] ?? 0);
if (!$clientId) { header('Location: /admin/clients.php'); exit; }

$cStmt = db()->prepare('SELECT * FROM clients WHERE id=?');
$cStmt->execute([$clientId]);
$client = $cStmt->fetch();
if (!$client) { header('Location: /admin/clients.php'); exit; }

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $stmt = db()->prepare("UPDATE clients SET first_name=?,last_name=?,email=?,phone=?,business_name=?,business_type=?,address=?,city=?,state=?,zip_code=?,plan=?,is_active=? WHERE id=?");
        $stmt->execute([
            trim($_POST['first_name']),trim($_POST['last_name']),strtolower(trim($_POST['email'])),
            trim($_POST['phone']??''),trim($_POST['business_name']??''),trim($_POST['business_type']??''),
            trim($_POST['address']??''),trim($_POST['city']??''),trim($_POST['state']??''),trim($_POST['zip_code']??''),
            $_POST['plan']??'free',isset($_POST['is_active'])?1:0,$clientId
        ]);
        $success = 'Client profile updated successfully!';
        // Refresh
        $cStmt->execute([$clientId]);
        $client = $cStmt->fetch();
    }

    if ($action === 'reset_password') {
        $newPass = $_POST['new_password'] ?? '';
        if (strlen($newPass) < 8) { $error = 'Password must be at least 8 characters.'; }
        else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost'=>12]);
            db()->prepare("UPDATE clients SET password=? WHERE id=?")->execute([$hash,$clientId]);
            $success = 'Password reset successfully!';
        }
    }
}
?>

<div class="app-layout">
  <aside class="sidebar admin-sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="logo-icon"><svg viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="#FF7421"/><path d="M12 28L20 12L28 28H12Z" fill="#fff"/></svg></div>
        <div class="logo-text"><span class="logo-name">Admin Panel</span><span class="logo-sub">Digital Tax</span></div>
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
        <div style="display:flex;align-items:center;gap:8px">
          <a href="/admin/clients.php" style="color:var(--gray-500);font-size:13px">Clients</a>
          <span style="color:var(--gray-300)">/</span>
          <a href="/admin/client-detail.php?id=<?= $clientId ?>" style="color:var(--gray-500);font-size:13px"><?= sanitize($client['first_name'].' '.$client['last_name']) ?></a>
          <span style="color:var(--gray-300)">/</span>
          <h1 style="font-size:18px">Edit</h1>
        </div>
      </div>
      <div class="header-actions">
        <a href="/admin/client-detail.php?id=<?= $clientId ?>" class="btn btn-ghost btn-sm">← Back to Profile</a>
      </div>
    </header>

    <div class="page-body" style="max-width:800px">
      <?php if ($success): ?>
        <div class="alert alert-success mb-16">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger mb-16">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Profile Form -->
      <div class="card mb-20">
        <div class="card-header"><span class="card-title">Edit Client Profile</span></div>
        <div class="card-body">
          <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="edit-section">
              <div class="edit-section-title">Personal Information</div>
              <div class="grid grid-2">
                <div class="form-group">
                  <label class="form-label">First Name</label>
                  <input type="text" name="first_name" class="form-control" value="<?= sanitize($client['first_name']) ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Last Name</label>
                  <input type="text" name="last_name" class="form-control" value="<?= sanitize($client['last_name']) ?>" required>
                </div>
              </div>
              <div class="grid grid-2">
                <div class="form-group">
                  <label class="form-label">Email Address</label>
                  <input type="email" name="email" class="form-control" value="<?= sanitize($client['email']) ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Phone</label>
                  <input type="text" name="phone" class="form-control" value="<?= sanitize($client['phone'] ?? '') ?>">
                </div>
              </div>
              <div class="grid grid-2">
                <div class="form-group">
                  <label class="form-label">Address</label>
                  <input type="text" name="address" class="form-control" value="<?= sanitize($client['address'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">City</label>
                  <input type="text" name="city" class="form-control" value="<?= sanitize($client['city'] ?? '') ?>">
                </div>
              </div>
              <div class="grid grid-2">
                <div class="form-group">
                  <label class="form-label">State</label>
                  <input type="text" name="state" class="form-control" value="<?= sanitize($client['state'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">ZIP Code</label>
                  <input type="text" name="zip_code" class="form-control" value="<?= sanitize($client['zip_code'] ?? '') ?>">
                </div>
              </div>
            </div>

            <div class="edit-section">
              <div class="edit-section-title">Business Information</div>
              <div class="grid grid-2">
                <div class="form-group">
                  <label class="form-label">Business Name</label>
                  <input type="text" name="business_name" class="form-control" value="<?= sanitize($client['business_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Business Type</label>
                  <select name="business_type" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach (['Sole Proprietor','LLC','S-Corp','C-Corp','Partnership','Freelancer'] as $bt): ?>
                      <option <?= ($client['business_type']??'')===$bt?'selected':'' ?>><?= $bt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

            <div class="edit-section">
              <div class="edit-section-title">Account Settings</div>
              <div class="grid grid-2">
                <div class="form-group">
                  <label class="form-label">Plan</label>
                  <select name="plan" class="form-control">
                    <?php foreach (['free','basic','professional','premium'] as $p): ?>
                      <option value="<?= $p ?>" <?= $client['plan']===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:18px">
                  <label class="checkbox-wrap">
                    <input type="checkbox" name="is_active" value="1" <?= $client['is_active']?'checked':'' ?>>
                    <span style="font-weight:600;color:var(--blue)">Account is Active</span>
                  </label>
                </div>
              </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px">
              <a href="/admin/client-detail.php?id=<?= $clientId ?>" class="btn btn-ghost">Cancel</a>
              <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Reset Password -->
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:var(--warning)">Reset Client Password</span></div>
        <div class="card-body">
          <div class="alert alert-warning mb-16">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>Set a new password for this client. Make sure to notify them of the change.</span>
          </div>
          <form method="POST" style="max-width:400px">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reset_password">
            <div class="form-group">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters" required minlength="8">
            </div>
            <button type="submit" class="btn" style="background:var(--warning);color:white;padding:10px 20px;font-weight:600" onclick="return confirm('Reset this client\'s password?')">
              Reset Password
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
</body>
</html>
