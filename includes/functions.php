<?php
// =============================================
// HELPER FUNCTIONS
// =============================================

function formatCurrency(float $amount, string $symbol = '£'): string {
    return $symbol . number_format(abs($amount), 2);
}

function formatDate(string $date, string $format = 'M d, Y'): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    return date($format, strtotime($date));
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function getTaxYear(): int {
    $month = (int)date('n');
    $year  = (int)date('Y');
    return $month < 4 ? $year - 1 : $year;
}

function getClientStats(int $clientId, int $year = null): array {
    $year = $year ?? getTaxYear();

    $income = db()->prepare('SELECT COALESCE(SUM(amount),0) FROM income WHERE client_id=? AND tax_year=?');
    $income->execute([$clientId, $year]);

    $expenses = db()->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE client_id=? AND tax_year=?');
    $expenses->execute([$clientId, $year]);

    $miles = db()->prepare('SELECT COALESCE(SUM(miles),0), COALESCE(SUM(deduction_amount),0) FROM mileage WHERE client_id=? AND tax_year=?');
    $miles->execute([$clientId, $year]);
    $milesRow = $miles->fetch(PDO::FETCH_NUM);

    $totalIncome   = (float)$income->fetchColumn();
    $totalExpenses = (float)$expenses->fetchColumn();
    $totalMiles    = (float)($milesRow[0] ?? 0);
    $mileDeduction = (float)($milesRow[1] ?? 0);
    $profit        = $totalIncome - $totalExpenses;

    return [
        'income'         => $totalIncome,
        'expenses'       => $totalExpenses,
        'profit'         => $profit,
        'miles'          => $totalMiles,
        'mile_deduction' => $mileDeduction,
        'year'           => $year,
    ];
}

function getMonthlyBreakdown(int $clientId, int $year): array {
    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $months[$m] = ['income' => 0, 'expenses' => 0, 'month' => date('M', mktime(0,0,0,$m,1))];
    }

    $incomeStmt = db()->prepare("SELECT MONTH(income_date) m, SUM(amount) s FROM income WHERE client_id=? AND tax_year=? GROUP BY m");
    $incomeStmt->execute([$clientId, $year]);
    foreach ($incomeStmt->fetchAll() as $row) {
        $months[(int)$row['m']]['income'] = (float)$row['s'];
    }

    $expStmt = db()->prepare("SELECT MONTH(expense_date) m, SUM(amount) s FROM expenses WHERE client_id=? AND tax_year=? GROUP BY m");
    $expStmt->execute([$clientId, $year]);
    foreach ($expStmt->fetchAll() as $row) {
        $months[(int)$row['m']]['expenses'] = (float)$row['s'];
    }

    return array_values($months);
}

function getExpenseCategories(int $clientId, int $year): array {
    $stmt = db()->prepare("SELECT category, SUM(amount) total FROM expenses WHERE client_id=? AND tax_year=? GROUP BY category ORDER BY total DESC");
    $stmt->execute([$clientId, $year]);
    return $stmt->fetchAll();
}

function getIncomeCategories(int $clientId, int $year): array {
    $stmt = db()->prepare("SELECT category, SUM(amount) total FROM income WHERE client_id=? AND tax_year=? GROUP BY category ORDER BY total DESC");
    $stmt->execute([$clientId, $year]);
    return $stmt->fetchAll();
}

function getRecentTransactions(int $clientId, int $limit = 10): array {
    $income = db()->prepare("SELECT id,'income' type,description,amount,income_date AS txn_date,category FROM income WHERE client_id=? ORDER BY income_date DESC LIMIT ?");
    $income->execute([$clientId, $limit]);

    $expenses = db()->prepare("SELECT id,'expense' type,description,amount,expense_date AS txn_date,category FROM expenses WHERE client_id=? ORDER BY expense_date DESC LIMIT ?");
    $expenses->execute([$clientId, $limit]);

    $all = array_merge($income->fetchAll(), $expenses->fetchAll());
    usort($all, fn($a, $b) => strcmp($b['txn_date'], $a['txn_date']));
    return array_slice($all, 0, $limit);
}

function uploadFile(array $file, string $prefix = 'file'): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error.'];
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'message' => 'File too large (max 5MB).'];
    }
    if (!in_array($file['type'], ALLOWED_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type.'];
    }
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'message' => 'Failed to save file.'];
    }
    return ['success' => true, 'filename' => $filename];
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword(string $pass): array {
    $errors = [];
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $pass)) $errors[] = 'Must contain at least one uppercase letter.';
    if (!preg_match('/[0-9]/', $pass)) $errors[] = 'Must contain at least one number.';
    return $errors;
}

function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    $init  = '';
    foreach ($parts as $p) { if ($p) $init .= strtoupper($p[0]); }
    return substr($init, 0, 2);
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff/60) . 'm ago';
    if ($diff < 86400)   return floor($diff/3600) . 'h ago';
    if ($diff < 604800)  return floor($diff/86400) . 'd ago';
    return date('M j', strtotime($datetime));
}
