<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/auth.css">
  <style>:root{--orange:#FF7421;--blue:#16295A;}</style>
</head>
<body>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isClientLoggedIn()) { header('Location: /dashboard.php'); exit; }

$token   = trim($_GET['token'] ?? $_POST['token'] ?? '');
$message = '';
$type    = '';
$done    = false;

if (!$token || !validateResetToken($token)) {
    $invalidToken = true;
} else {
    $invalidToken = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$invalidToken) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $type    = 'error';
    } elseif (strlen($_POST['password'] ?? '') < 8) {
        $message = 'Password must be at least 8 characters.';
        $type    = 'error';
    } elseif (($_POST['password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
        $message = 'Passwords do not match.';
        $type    = 'error';
    } else {
        $result = resetPassword($token, $_POST['password']);
        if ($result['success']) {
            $done    = true;
            $message = 'Password reset successfully! Redirecting to login...';
            $type    = 'success';
        } else {
            $message = $result['message'];
            $type    = 'error';
        }
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

  <div class="auth-container" style="justify-content:center">
    <div class="auth-form-panel" style="max-width:580px;width:100%">
      <div class="auth-card">
        <div class="auth-card-header">
          <div style="text-align:center;margin-bottom:20px">
            <img src="/assets/images/logo-full.png" alt="Digital Tax Accounting" style="width:220px;max-width:100%;height:auto;object-fit:contain;">
          </div>
          <?php if ($invalidToken): ?>
            <h2>Invalid Reset Link</h2>
            <p>This link is expired or has already been used</p>
          <?php elseif ($done): ?>
            <h2>Password Reset!</h2>
            <p>Your password has been updated successfully</p>
          <?php else: ?>
            <h2>Set New Password</h2>
            <p>Choose a strong password for your account</p>
          <?php endif; ?>
        </div>

        <?php if ($message): ?>
          <div class="auth-alert <?= $type ?> show">
            <?php if ($type === 'success'): ?>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <?php else: ?>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?php endif; ?>
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>

        <?php if ($invalidToken): ?>
          <div style="text-align:center;padding:12px 0 8px">
            <div style="width:64px;height:64px;background:#FEF2F2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
              <svg viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" width="30" height="30"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <a href="/forgot-password.php" class="auth-submit" style="display:block;text-decoration:none;text-align:center;">Request a New Link</a>
          </div>

        <?php elseif ($done): ?>
          <div style="text-align:center;padding:12px 0 8px">
            <div style="width:64px;height:64px;background:#ECFDF5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
              <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" width="32" height="32"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <a href="/login.php" class="auth-submit" style="display:block;text-decoration:none;text-align:center;">Go to Login</a>
          </div>

        <?php else: ?>
          <form method="POST" class="auth-form">
            <?= csrfField() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
              <label class="form-label">New Password</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input type="password" name="password" id="password" class="form-control"
                       placeholder="••••••••" required autocomplete="new-password" minlength="8">
                <button type="button" class="toggle-password" onclick="togglePwd('password','eye1')">
                  <svg id="eye1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Confirm New Password</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                       placeholder="••••••••" required autocomplete="new-password">
                <button type="button" class="toggle-password" onclick="togglePwd('confirm_password','eye2')">
                  <svg id="eye2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>

            <button type="submit" class="auth-submit">Reset Password</button>
          </form>
        <?php endif; ?>

        <div class="auth-footer">
          Remember your password? <a href="/login.php">Sign in</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePwd(id, iconId) {
      const inp = document.getElementById(id);
      const ico = document.getElementById(iconId);
      if (inp.type === 'password') {
        inp.type = 'text';
        ico.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
      } else {
        inp.type = 'password';
        ico.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
      }
    }
    <?php if ($done): ?>
    setTimeout(() => window.location.href = '/login.php', 2500);
    <?php endif; ?>
  </script>
</body>
</html>
