<?php
// =============================================
// AUTHENTICATION HELPERS
// =============================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

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
