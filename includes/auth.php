<?php
// =============================================
// AUTHENTICATION HELPERS
// =============================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mailer.php';

// ---- CLIENT AUTH ----

function isClientLoggedIn(): bool {
    return isset($_SESSION['client_id']) && !empty($_SESSION['client_id']);
}

function requireClientLogin(): void {
    if (!isClientLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function getClient(): ?array {
    if (!isClientLoggedIn()) return null;
    $stmt = db()->prepare('SELECT * FROM clients WHERE id = ? AND is_active = 1');
    $stmt->execute([$_SESSION['client_id']]);
    return $stmt->fetch() ?: null;
}

function loginClient(string $email, string $password, bool $remember = false): array {
    $stmt = db()->prepare('SELECT * FROM clients WHERE email = ? AND is_active = 1');
    $stmt->execute([strtolower(trim($email))]);
    $client = $stmt->fetch();

    if (!$client || !password_verify($password, $client['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    // Update last login
    db()->prepare('UPDATE clients SET last_login = NOW() WHERE id = ?')->execute([$client['id']]);

    $_SESSION['client_id']   = $client['id'];
    $_SESSION['client_name'] = $client['first_name'] . ' ' . $client['last_name'];
    $_SESSION['client_email'] = $client['email'];

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + REMEMBER_LIFETIME);
        db()->prepare('INSERT INTO client_sessions (client_id, token, ip_address, user_agent, expires_at) VALUES (?,?,?,?,?)')
             ->execute([$client['id'], $token, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $expires]);
        setcookie('remember_token', $token, time() + REMEMBER_LIFETIME, '/', '', false, true);
    }

    return ['success' => true, 'message' => 'Login successful.'];
}

function registerClient(array $data): array {
    $email = strtolower(trim($data['email']));

    // Check duplicate
    $stmt = db()->prepare('SELECT id FROM clients WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }

    $hash  = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $token = bin2hex(random_bytes(32));

    $stmt = db()->prepare('
        INSERT INTO clients (first_name, last_name, email, password, phone, business_name, verification_token)
        VALUES (?,?,?,?,?,?,?)
    ');
    $stmt->execute([
        trim($data['first_name']),
        trim($data['last_name']),
        $email,
        $hash,
        trim($data['phone'] ?? ''),
        trim($data['business_name'] ?? ''),
        $token
    ]);
    $clientId = db()->lastInsertId();

    // Auto-login after registration
    $_SESSION['client_id']    = $clientId;
    $_SESSION['client_name']  = trim($data['first_name']) . ' ' . trim($data['last_name']);
    $_SESSION['client_email'] = $email;

    return ['success' => true, 'message' => 'Account created successfully!', 'client_id' => $clientId];
}

function logoutClient(): void {
    // Clear remember cookie
    if (isset($_COOKIE['remember_token'])) {
        db()->prepare('DELETE FROM client_sessions WHERE token = ?')->execute([$_COOKIE['remember_token']]);
        setcookie('remember_token', '', time() - 3600, '/');
    }
    unset($_SESSION['client_id'], $_SESSION['client_name'], $_SESSION['client_email']);
    session_regenerate_id(true);
}

// ---- ADMIN AUTH ----

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

function getAdmin(): ?array {
    if (!isAdminLoggedIn()) return null;
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ? AND is_active = 1');
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

function loginAdmin(string $email, string $password): array {
    $stmt = db()->prepare('SELECT * FROM admins WHERE email = ? AND is_active = 1');
    $stmt->execute([strtolower(trim($email))]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);

    $_SESSION['admin_id']   = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_role'] = $admin['role'];

    return ['success' => true, 'message' => 'Admin login successful.'];
}

function logoutAdmin(): void {
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
    session_regenerate_id(true);
}

// ---- PASSWORD RESET ----

function requestPasswordReset(string $email): array {
    $email = strtolower(trim($email));
    $stmt  = db()->prepare('SELECT id, first_name FROM clients WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $client = $stmt->fetch();

    if (!$client) {
        return ['success' => true]; // Don't reveal if email exists
    }

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    db()->prepare('UPDATE clients SET reset_token = ?, reset_expires_at = ? WHERE id = ?')
         ->execute([$token, $expires, $client['id']]);

    $link = APP_URL . '/reset-password.php?token=' . $token;
    sendPasswordResetEmail($email, $client['first_name'], $link);

    return ['success' => true];
}

function validateResetToken(string $token): bool {
    $stmt = db()->prepare('SELECT id FROM clients WHERE reset_token = ? AND reset_expires_at > NOW()');
    $stmt->execute([$token]);
    return (bool)$stmt->fetch();
}

function resetPassword(string $token, string $newPassword): array {
    $stmt = db()->prepare('SELECT id FROM clients WHERE reset_token = ? AND reset_expires_at > NOW()');
    $stmt->execute([$token]);
    $client = $stmt->fetch();

    if (!$client) {
        return ['success' => false, 'message' => 'This reset link is invalid or has expired.'];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    db()->prepare('UPDATE clients SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?')
         ->execute([$hash, $client['id']]);

    return ['success' => true];
}

function sendPasswordResetEmail(string $to, string $name, string $link): void {
    $subject  = 'Reset Your Password — ' . APP_NAME;
    $year     = date('Y');
    $appName  = APP_NAME;

    $html = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Inter,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 0;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:white;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:100%;">
      <tr><td style="background:#16295A;padding:28px 40px;text-align:center;">
        <h1 style="color:white;margin:0;font-size:20px;font-weight:800;">{$appName}</h1>
      </td></tr>
      <tr><td style="padding:40px;">
        <h2 style="color:#16295A;font-size:22px;margin:0 0 12px;">Reset Your Password</h2>
        <p style="color:#6B7D99;font-size:15px;line-height:1.6;margin:0 0 28px;">
          Hi {$name}, we received a request to reset your password.<br>
          Click the button below to set a new one. This link expires in <strong>1 hour</strong>.
        </p>
        <div style="text-align:center;margin-bottom:32px;">
          <a href="{$link}" style="display:inline-block;background:#FF7421;color:white;text-decoration:none;padding:14px 40px;border-radius:10px;font-size:15px;font-weight:700;">Reset Password</a>
        </div>
        <p style="color:#9AAAC0;font-size:13px;line-height:1.6;margin:0 0 20px;">If you didn't request this, you can safely ignore this email — your password will remain unchanged.</p>
        <hr style="border:none;border-top:1px solid #E2E7F0;margin:0 0 16px;">
        <p style="color:#9AAAC0;font-size:12px;margin:0;">Or copy this link into your browser:<br>
          <a href="{$link}" style="color:#FF7421;word-break:break-all;">{$link}</a>
        </p>
      </td></tr>
      <tr><td style="background:#F8F9FC;padding:18px 40px;text-align:center;">
        <p style="color:#9AAAC0;font-size:12px;margin:0;">© {$year} {$appName}. All rights reserved.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;

    sendMail($to, $subject, $html);
}

// ---- CSRF ----

function generateCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrf()) . '">';
}
