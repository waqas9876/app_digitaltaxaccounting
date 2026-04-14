<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) jsonResponse(['success'=>false,'message'=>'Unauthorized'],401);

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = $input['action'] ?? '';
$adminId = (int)$_SESSION['admin_id'];

if (!verifyCsrf($input['csrf_token'] ?? '')) jsonResponse(['success'=>false,'message'=>'Invalid CSRF token.'],403);

switch ($action) {
    case 'activate':
    case 'deactivate':
        $clientId = (int)($input['client_id'] ?? 0);
        $val      = $action === 'activate' ? 1 : 0;
        $stmt = db()->prepare("UPDATE clients SET is_active=? WHERE id=?");
        $stmt->execute([$val,$clientId]);
        jsonResponse(['success'=>true,'message'=>'Client '.($val?'activated':'deactivated').'!']);

    case 'update_plan':
        $clientId = (int)($input['client_id'] ?? 0);
        $plan     = $input['plan'] ?? 'free';
        if (!in_array($plan,['free','basic','professional','premium'])) jsonResponse(['success'=>false,'message'=>'Invalid plan.']);
        db()->prepare("UPDATE clients SET plan=? WHERE id=?")->execute([$plan,$clientId]);
        jsonResponse(['success'=>true,'message'=>'Plan updated!']);

    case 'add_note':
        $clientId = (int)($input['client_id'] ?? 0);
        $note     = trim($input['note'] ?? '');
        if (!$note) jsonResponse(['success'=>false,'message'=>'Note content required.']);
        $stmt = db()->prepare("INSERT INTO admin_client_notes (client_id,admin_id,note) VALUES (?,?,?)");
        $stmt->execute([$clientId,$adminId,$note]);
        jsonResponse(['success'=>true,'message'=>'Note added!']);

    case 'update_tax':
        $clientId  = (int)($input['client_id'] ?? 0);
        $taxYear   = (int)($input['tax_year'] ?? getTaxYear());
        $status    = $input['status'] ?? 'not_started';
        $adminNote = trim($input['admin_notes'] ?? '');

        if (!in_array($status,['not_started','in_progress','filed','accepted','rejected'])) $status='not_started';

        $check = db()->prepare("SELECT id FROM taxes WHERE client_id=? AND tax_year=?");
        $check->execute([$clientId,$taxYear]);
        if ($check->fetch()) {
            $stmt = db()->prepare("UPDATE taxes SET status=?,admin_notes=? WHERE client_id=? AND tax_year=?");
            $stmt->execute([$status,$adminNote,$clientId,$taxYear]);
        } else {
            $stmt = db()->prepare("INSERT INTO taxes (client_id,tax_year,status,admin_notes) VALUES (?,?,?,?)");
            $stmt->execute([$clientId,$taxYear,$status,$adminNote]);
        }
        jsonResponse(['success'=>true,'message'=>'Tax record updated!']);

    case 'get_stats':
        $stats = [
            'total_clients' => db()->query("SELECT COUNT(*) FROM clients WHERE is_active=1")->fetchColumn(),
            'new_this_month' => db()->query("SELECT COUNT(*) FROM clients WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn(),
            'total_income'  => db()->query("SELECT COALESCE(SUM(amount),0) FROM income WHERE tax_year=".getTaxYear())->fetchColumn(),
            'total_expenses'=> db()->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE tax_year=".getTaxYear())->fetchColumn(),
        ];
        jsonResponse(['success'=>true,'data'=>$stats]);

    default:
        jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
}
