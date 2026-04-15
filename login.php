<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/auth.css">
  <style>
    :root { --orange:#FF7421; --blue:#16295A; }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in
if (isClientLoggedIn()) { header('Location: /dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $result = loginClient($_POST['email'] ?? '', $_POST['password'] ?? '', !empty($_POST['remember']));
        if ($result['success']) {
            header('Location: /dashboard.php');
            exit;
        }
        $error = $result['message'];
    }
}
?>

  <div class="auth-bg">
    <div class="auth-particles"></div>
  </div>

  <div class="auth-container">
    <!-- Brand Panel -->
    <div class="auth-brand">
      <div class="brand-logo">
        <div class="brand-logo-icon">
          <img src="/assets/images/logo.png" alt="Digital Tax Accounting">
        </div>
      </div>

      <h1 class="brand-headline">Your Taxes,<br><span>Simplified</span></h1>
      <p class="brand-sub">Track income, expenses, mileage, and more. Get your taxes filed stress-free with our all-in-one portal.</p>

      <ul class="brand-features">
        <li>
          <span class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
          </span>
          Track income & expenses in real time
        </li>
        <li>
          <span class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/></svg>
          </span>
          Auto-calculate mileage deductions
        </li>
        <li>
          <span class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </span>
          Tax summaries & year-end reports
        </li>
        <li>
          <span class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </span>
          Secure, encrypted data storage
        </li>
      </ul>

      <div class="brand-stats">
        <div class="brand-stat"><span class="num">500+</span><span class="lbl">Clients</span></div>
        <div class="brand-stat"><span class="num">$2M+</span><span class="lbl">Tax Savings</span></div>
        <div class="brand-stat"><span class="num">99%</span><span class="lbl">Accuracy</span></div>
      </div>
    </div>

    <!-- Form Panel -->
    <div class="auth-form-panel">
      <div class="auth-card">
        <div class="auth-card-header">
          <h2>Welcome Back</h2>
          <p>Sign in to access your client portal</p>
        </div>

        <?php if ($error): ?>
          <div class="auth-alert error show">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <div id="authAlert" class="auth-alert"></div>

        <form method="POST" class="auth-form" id="loginForm" novalidate>
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

          <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrap">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </span>
              <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
              <button type="button" class="toggle-password" onclick="togglePwd('password')">
                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>

          <div class="remember-row">
            <label class="checkbox-wrap">
              <input type="checkbox" name="remember" <?= !empty($_POST['remember']) ? 'checked' : '' ?>>
              <span>Remember me</span>
            </label>
            <a href="/forgot-password.php" class="forgot-link">Forgot password?</a>
          </div>

          <button type="submit" class="auth-submit" id="loginBtn">
            Sign In
          </button>
        </form>

        <div class="auth-footer">
          Don't have an account? <a href="/signup.php">Create one free</a>
        </div>

        <div style="text-align:center;margin-top:12px;font-size:12px;color:#9AAAC0;">
          Admin? <a href="/admin/login.php" style="color:#16295A;font-weight:600;">Admin Login →</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePwd(id) {
      const inp = document.getElementById(id);
      const ico = document.getElementById('eyeIcon');
      if (inp.type === 'password') {
        inp.type = 'text';
        ico.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
      } else {
        inp.type = 'password';
        ico.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
      }
    }

    // Generate particles
    const container = document.querySelector('.auth-particles');
    for (let i = 0; i < 20; i++) {
      const p = document.createElement('div');
      p.className = 'particle';
      p.style.cssText = `left:${Math.random()*100}%;width:${2+Math.random()*4}px;height:${2+Math.random()*4}px;animation-duration:${6+Math.random()*12}s;animation-delay:${Math.random()*8}s;`;
      container.appendChild(p);
    }
  </script>
</body>
</html>
