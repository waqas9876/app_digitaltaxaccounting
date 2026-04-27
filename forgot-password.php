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
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isClientLoggedIn()) { header('Location: /dashboard.php'); exit; }

$step    = 1; // 1 = enter email, 2 = enter new password
$message = '';
$type    = '';
$email   = '';

// Step 1: verify email exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['password'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please refresh and try again.';
        $type    = 'error';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $stmt  = db()->prepare('SELECT id FROM clients WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $step = 2; // email found, show password fields
        } else {
            $message = 'No account found with that email address.';
            $type    = 'error';
        }
    }
}

// Step 2: reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'], $_POST['confirm_password'], $_POST['reset_email'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please refresh and try again.';
        $type    = 'error';
        $step    = 1;
    } elseif (strlen($_POST['password']) < 8) {
        $message = 'Password must be at least 8 characters.';
        $type    = 'error';
        $email   = $_POST['reset_email'];
        $step    = 2;
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $message = 'Passwords do not match.';
        $type    = 'error';
        $email   = $_POST['reset_email'];
        $step    = 2;
    } else {
        $email = strtolower(trim($_POST['reset_email']));
        $hash  = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt  = db()->prepare('UPDATE clients SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE email = ?');
        $stmt->execute([$hash, $email]);

        if ($stmt->rowCount() > 0) {
            $message = 'Password reset successfully! Redirecting to login...';
            $type    = 'success';
            $step    = 3; // done
        } else {
            $message = 'Something went wrong. Please try again.';
            $type    = 'error';
            $step    = 1;
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
          <?php if ($step === 1): ?>
            <h2>Forgot Password?</h2>
            <p>Enter your email address to reset your password</p>
          <?php elseif ($step === 2): ?>
            <h2>Set New Password</h2>
            <p>Choose a strong new password for <strong><?= htmlspecialchars($email) ?></strong></p>
          <?php else: ?>
            <h2>Password Reset!</h2>
            <p>Your password has been updated successfully</p>
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

        <?php if ($step === 1): ?>
        <!-- STEP 1: Email -->
        <form method="POST" class="auth-form">
          <?= csrfField() ?>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-wrap">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </span>
              <input type="email" name="email" class="form-control" placeholder="you@example.com"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
            </div>
          </div>
          <button type="submit" class="auth-submit">Continue</button>
        </form>

        <?php elseif ($step === 2): ?>
        <!-- STEP 2: New Password -->
        <form method="POST" class="auth-form">
          <?= csrfField() ?>
          <input type="hidden" name="reset_email" value="<?= htmlspecialchars($email) ?>">
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

        <?php else: ?>
        <!-- STEP 3: Done -->
        <div style="text-align:center;padding:12px 0 8px">
          <div style="width:64px;height:64px;background:#ECFDF5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" width="32" height="32"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <a href="/login.php" class="auth-submit" style="display:block;text-decoration:none;text-align:center;">Go to Login</a>
        </div>
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
    <?php if ($step === 3): ?>
    setTimeout(() => window.location.href = '/login.php', 2500);
    <?php endif; ?>
  </script>
</body>
</html>
