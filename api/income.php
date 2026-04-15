<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isClientLoggedIn()) {
    jsonResponse(['success'=>false,'message'=>'Unauthorized'],401);
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$action   = $input['action'] ?? '';
$clientId = (int)$_SESSION['client_id'];

if (!verifyCsrf($input['csrf_token'] ?? '')) {
    jsonResponse(['success'=>false,'message'=>'Invalid CSRF token.'],403);
}

switch ($action) {
    case 'create':
        $desc      = trim($input['description'] ?? '');
        $amount    = (float)($input['amount'] ?? 0);
        $date      = $input['income_date'] ?? date('Y-m-d');
        $cat       = trim($input['category'] ?? 'Employment Income');
        $method    = trim($input['payment_method'] ?? '');
        $reference = trim($input['reference'] ?? '');
        $notes     = trim($input['notes'] ?? '');
        $taxYear   = (int)($input['tax_year'] ?? getTaxYear());

        if (!$desc || $amount <= 0) jsonResponse(['success'=>false,'message'=>'Description and amount are required.']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) jsonResponse(['success'=>false,'message'=>'Invalid date.']);

        $stmt = db()->prepare("INSERT INTO income (client_id,description,category,amount,income_date,payment_method,reference,notes,tax_year) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$clientId,$desc,$cat,$amount,$date,$method,$reference,$notes,$taxYear]);
        jsonResponse(['success'=>true,'message'=>'Income added successfully!','id'=>db()->lastInsertId()]);

    case 'get':
        $id   = (int)($input['id'] ?? 0);
        $stmt = db()->prepare("SELECT * FROM income WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        $row  = $stmt->fetch();
        if (!$row) jsonResponse(['success'=>false,'message'=>'Record not found.'],404);
        jsonResponse(['success'=>true,'data'=>$row]);

    case 'update':
        $id        = (int)($input['id'] ?? 0);
        $desc      = trim($input['description'] ?? '');
        $amount    = (float)($input['amount'] ?? 0);
        $date      = $input['income_date'] ?? '';
        $cat       = trim($input['category'] ?? 'Employment Income');
        $method    = trim($input['payment_method'] ?? '');
        $reference = trim($input['reference'] ?? '');
        $notes     = trim($input['notes'] ?? '');

        if (!$desc || $amount <= 0 || !$date) jsonResponse(['success'=>false,'message'=>'Missing required fields.']);

        $stmt = db()->prepare("UPDATE income SET description=?,category=?,amount=?,income_date=?,payment_method=?,reference=?,notes=? WHERE id=? AND client_id=?");
        $stmt->execute([$desc,$cat,$amount,$date,$method,$reference,$notes,$id,$clientId]);
        jsonResponse(['success'=>true,'message'=>'Income updated!']);

    case 'delete':
        $id   = (int)($input['id'] ?? 0);
        $stmt = db()->prepare("DELETE FROM income WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        if ($stmt->rowCount() === 0) jsonResponse(['success'=>false,'message'=>'Record not found.'],404);
        jsonResponse(['success'=>true,'message'=>'Income deleted.']);

    default:
        jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
}
