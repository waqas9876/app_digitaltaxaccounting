<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/auth.css">
  <style>
    :root { --orange:#FF7421; --blue:#16295A; }
    .admin-badge {
      display:inline-flex;align-items:center;gap:6px;background:rgba(255,116,33,.15);
      border:1px solid rgba(255,116,33,.3);color:#FF7421;padding:4px 12px;
      border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;
    }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdminLoggedIn()) { header('Location: /admin/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $result = loginAdmin($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) { header('Location: /admin/dashboard.php'); exit; }
        $error = $result['message'];
    }
}
?>

  <div style="position:fixed;inset:0;z-index:0;background:#080F20"></div>
  <div style="position:fixed;inset:0;z-index:0;
    background-image:linear-gradient(to right,rgba(255,116,33,.12) 1px,transparent 1px),linear-gradient(to bottom,rgba(255,116,33,.12) 1px,transparent 1px);
    background-size:32px 32px;
    -webkit-mask-image:radial-gradient(ellipse at center,white 30%,transparent 80%);
    mask-image:radial-gradient(ellipse at center,white 30%,transparent 80%);
  "></div>

  <div style="position:relative;z-index:10;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px">
    <div class="auth-card" style="max-width:580px;width:100%">
      <div style="text-align:center;margin-bottom:28px">
        <div style="width:280px;max-width:100%;margin:0 auto 18px">
          <img src="/assets/images/logo-full.png" alt="Digital Tax Accounting" style="width:100%;height:auto;object-fit:contain;">
        </div>
        <div class="admin-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Admin Access
        </div>
        <h2 style="font-size:24px;font-weight:800;color:#16295A;margin-bottom:4px">Admin Portal</h2>
        <p style="font-size:14px;color:#6B7D99">Digital Tax Accounting Management</p>
      </div>

      <?php if ($error): ?>
        <div class="auth-alert error show">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Admin Email</label>
          <div class="input-wrap">
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
            <input type="email" name="email" class="form-control" placeholder="admin@digitaltaxaccounting.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:24px">
          <label class="form-label">Password</label>
          <div class="input-wrap">
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            <button type="button" class="toggle-password" onclick="togglePwd()">
              <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="auth-submit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Sign In to Admin
        </button>
      </form>

      <div class="auth-footer">
        <a href="/login.php" style="color:#16295A;font-size:13px">← Back to Client Portal</a>
      </div>
    </div>
  </div>

  <script>
    function togglePwd() {
      const inp = document.getElementById('password');
      const ico = document.getElementById('eyeIcon');
      if (inp.type === 'password') {
        inp.type = 'text';
        ico.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
      } else {
        inp.type = 'password';
        ico.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
      }
    }
  </script>
</body>
</html>
