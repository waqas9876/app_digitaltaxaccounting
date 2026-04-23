<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — Digital Tax Accounting</title>
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

$message = '';
$type    = '';
$sent    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please refresh and try again.';
        $type    = 'error';
    } else {
        requestPasswordReset($_POST['email'] ?? '');
        $sent    = true;
        $message = 'If an account exists with that email, a reset link has been sent. Check your inbox.';
        $type    = 'success';
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
          <?php if (!$sent): ?>
            <h2>Forgot Password?</h2>
            <p>Enter your email and we'll send you a reset link</p>
          <?php else: ?>
            <h2>Check Your Email</h2>
            <p>A reset link has been sent if your account exists</p>
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

        <?php if (!$sent): ?>
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
          <button type="submit" class="auth-submit">Send Reset Link</button>
        </form>
        <?php else: ?>
          <div style="text-align:center;padding:10px 0 4px">
            <div style="width:64px;height:64px;background:#ECFDF5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
              <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" width="32" height="32"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <p style="font-size:13px;color:#6B7D99;line-height:1.6">Didn't receive it? Check your spam folder or <a href="/forgot-password.php" style="color:#FF7421;font-weight:600;">try again</a>.</p>
          </div>
        <?php endif; ?>

        <div class="auth-footer">
          Remember your password? <a href="/login.php">Sign in</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
