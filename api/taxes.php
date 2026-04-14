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

if ($action === 'save') {
    $taxYear    = (int)($input['tax_year'] ?? getTaxYear());
    $filingStatus = in_array($input['filing_status']??'',['single','married_jointly','married_separately','head_of_household','qualifying_widow']) ? $input['filing_status'] : 'single';
    $status     = in_array($input['status']??'',['not_started','in_progress','filed','accepted','rejected']) ? $input['status'] : 'not_started';
    $grossInc   = (float)($input['gross_income'] ?? 0);
    $deductions = (float)($input['total_deductions'] ?? 0);
    $taxable    = (float)($input['taxable_income'] ?? 0);
    $estTax     = (float)($input['estimated_tax'] ?? 0);
    $taxPaid    = (float)($input['tax_paid'] ?? 0);
    $refund     = (float)($input['refund_owed'] ?? 0);
    $notes      = trim($input['notes'] ?? '');

    // Upsert
    $check = db()->prepare("SELECT id FROM taxes WHERE client_id=? AND tax_year=?");
    $check->execute([$clientId,$taxYear]);
    if ($check->fetch()) {
        $stmt = db()->prepare("UPDATE taxes SET filing_status=?,status=?,gross_income=?,total_deductions=?,taxable_income=?,estimated_tax=?,tax_paid=?,refund_owed=?,notes=? WHERE client_id=? AND tax_year=?");
        $stmt->execute([$filingStatus,$status,$grossInc,$deductions,$taxable,$estTax,$taxPaid,$refund,$notes,$clientId,$taxYear]);
    } else {
        $stmt = db()->prepare("INSERT INTO taxes (client_id,tax_year,filing_status,status,gross_income,total_deductions,taxable_income,estimated_tax,tax_paid,refund_owed,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$clientId,$taxYear,$filingStatus,$status,$grossInc,$deductions,$taxable,$estTax,$taxPaid,$refund,$notes]);
    }
    jsonResponse(['success'=>true,'message'=>'Tax information saved!']);
}

jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
