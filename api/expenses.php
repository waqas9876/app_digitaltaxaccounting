<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isClientLoggedIn()) jsonResponse(['success'=>false,'message'=>'Unauthorized'],401);

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$action   = $input['action'] ?? '';
$clientId = (int)$_SESSION['client_id'];

if (!verifyCsrf($input['csrf_token'] ?? '')) jsonResponse(['success'=>false,'message'=>'Invalid CSRF token.'],403);

switch ($action) {
    case 'create':
        $desc    = trim($input['description'] ?? '');
        $amount  = (float)($input['amount'] ?? 0);
        $date    = $input['expense_date'] ?? date('Y-m-d');
        $cat     = trim($input['category'] ?? 'General');
        $vendor  = trim($input['vendor'] ?? '');
        $method  = trim($input['payment_method'] ?? '');
        $ref     = trim($input['reference'] ?? '');
        $notes   = trim($input['notes'] ?? '');
        $deduct  = (int)($input['is_deductible'] ?? 1);
        $taxYear = (int)($input['tax_year'] ?? getTaxYear());

        if (!$desc || $amount <= 0) jsonResponse(['success'=>false,'message'=>'Description and amount required.']);

        $stmt = db()->prepare("INSERT INTO expenses (client_id,description,category,amount,expense_date,payment_method,vendor,reference,notes,is_deductible,tax_year) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$clientId,$desc,$cat,$amount,$date,$method,$vendor,$ref,$notes,$deduct,$taxYear]);
        jsonResponse(['success'=>true,'message'=>'Expense added!','id'=>db()->lastInsertId()]);

    case 'get':
        $id   = (int)($input['id'] ?? 0);
        $stmt = db()->prepare("SELECT * FROM expenses WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        $row  = $stmt->fetch();
        if (!$row) jsonResponse(['success'=>false,'message'=>'Not found.'],404);
        jsonResponse(['success'=>true,'data'=>$row]);

    case 'delete':
        $id   = (int)($input['id'] ?? 0);
        $stmt = db()->prepare("DELETE FROM expenses WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        if ($stmt->rowCount()===0) jsonResponse(['success'=>false,'message'=>'Not found.'],404);
        jsonResponse(['success'=>true,'message'=>'Expense deleted.']);

    default:
        jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
}
