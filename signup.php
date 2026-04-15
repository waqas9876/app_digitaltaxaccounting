<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — Digital Tax Accounting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/auth.css">
  <style>
    :root { --orange:#FF7421; --blue:#16295A; }
    .auth-form .form-control { padding: 11px 14px 11px 44px; }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isClientLoggedIn()) { header('Location: /dashboard.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Passwords do not match.';
    } elseif (strlen($_POST['password']) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = registerClient($_POST);
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

      <h1 class="brand-headline">Start Filing<br><span>Smarter</span> Today</h1>
      <p class="brand-sub">Create your free account and get access to all your tax tracking tools in one place.</p>

      <ul class="brand-features">
        <li>
          <span class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </span>
          Fully secure & encrypted
        </li>
        <li>
          <span class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          </span>
          Beautiful, easy-to-use dashboard
        </li>
        <li>
          <span class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </span>
          Real-time financial insights
        </li>
        <li>
          <span class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </span>
          Start free, upgrade anytime
        </li>
      </ul>

      <div class="brand-stats">
        <div class="brand-stat"><span class="num">Free</span><span class="lbl">To Start</span></div>
        <div class="brand-stat"><span class="num">5 min</span><span class="lbl">Setup</span></div>
        <div class="brand-stat"><span class="num">24/7</span><span class="lbl">Support</span></div>
      </div>
    </div>

    <!-- Form Panel -->
    <div class="auth-form-panel">
      <div class="auth-card">
        <div class="auth-card-header">
          <h2>Create Your Account</h2>
          <p>Join hundreds of clients managing taxes smarter</p>
        </div>

        <?php if ($error): ?>
          <div class="auth-alert error show">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="auth-form" id="signupForm" novalidate>
          <?= csrfField() ?>

          <div class="auth-form-row">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <input type="text" name="first_name" class="form-control" placeholder="John"
                       value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <input type="text" name="last_name" class="form-control" placeholder="Smith"
                       value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-wrap">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </span>
              <input type="email" name="email" class="form-control" placeholder="you@example.com"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <div class="input-wrap">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.18 6.18l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              </span>
              <input type="tel" name="phone" class="form-control" placeholder="+1 (555) 000-0000"
                     value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Business Name <span style="color:#9AAAC0;font-weight:400;">(optional)</span></label>
            <div class="input-wrap">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              </span>
              <input type="text" name="business_name" class="form-control" placeholder="ABC Consulting LLC"
                     value="<?= htmlspecialchars($_POST['business_name'] ?? '') ?>">
            </div>
          </div>

          <div class="auth-form-row">
            <div class="form-group">
              <label class="form-label">Password</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••"
                       required autocomplete="new-password" oninput="checkStrength(this.value)">
                <button type="button" class="toggle-password" onclick="togglePwd('password','eye1')">
                  <svg id="eye1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <div class="password-strength show" id="strengthWrap">
                <div class="strength-bars">
                  <div class="strength-bar" id="sb1"></div>
                  <div class="strength-bar" id="sb2"></div>
                  <div class="strength-bar" id="sb3"></div>
                  <div class="strength-bar" id="sb4"></div>
                </div>
                <span class="strength-label" id="strengthLabel">Enter a password</span>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Confirm Password</label>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                       placeholder="••••••••" required autocomplete="new-password">
                <button type="button" class="toggle-password" onclick="togglePwd('confirm_password','eye2')">
                  <svg id="eye2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>
          </div>

          <div style="margin-bottom:20px;">
            <label class="checkbox-wrap">
              <input type="checkbox" name="agree_terms" required>
              <span>I agree to the <a href="#" style="color:#FF7421;">Terms of Service</a> and <a href="#" style="color:#FF7421;">Privacy Policy</a></span>
            </label>
          </div>

          <button type="submit" class="auth-submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Create Free Account
          </button>
        </form>

        <div class="auth-footer">
          Already have an account? <a href="/login.php">Sign in</a>
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

    function checkStrength(val) {
      const bars  = [sb1, sb2, sb3, sb4];
      const label = document.getElementById('strengthLabel');
      let score = 0;
      if (val.length >= 8)             score++;
      if (/[A-Z]/.test(val))           score++;
      if (/[0-9]/.test(val))           score++;
      if (/[^A-Za-z0-9]/.test(val))    score++;

      const levels = ['', 'weak', 'fair', 'good', 'strong'];
      const labels = ['Enter a password', 'Weak', 'Fair', 'Good', 'Strong'];
      const colors = ['', '#EF4444', '#F59E0B', '#3B82F6', '#10B981'];

      bars.forEach((b, i) => {
        b.className = 'strength-bar' + (i < score ? ' active ' + levels[score] : '');
      });
      label.textContent = labels[score];
      label.style.color = colors[score];
    }

    // Particles
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
