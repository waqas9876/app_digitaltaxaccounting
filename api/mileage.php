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
        $purpose = trim($input['purpose'] ?? '');
        $miles   = (float)($input['miles'] ?? 0);
        $date    = $input['trip_date'] ?? date('Y-m-d');
        $from    = trim($input['from_location'] ?? '');
        $to      = trim($input['to_location'] ?? '');
        $vehicle = trim($input['vehicle'] ?? '');
        $notes   = trim($input['notes'] ?? '');
        $taxYear = (int)($input['tax_year'] ?? getTaxYear());
        $rate    = STANDARD_MILEAGE_RATE;

        if (!$purpose || $miles <= 0) jsonResponse(['success'=>false,'message'=>'Purpose and miles are required.']);

        $stmt = db()->prepare("INSERT INTO mileage (client_id,trip_date,purpose,from_location,to_location,miles,rate_per_mile,vehicle,notes,tax_year) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$clientId,$date,$purpose,$from,$to,$miles,$rate,$vehicle,$notes,$taxYear]);
        jsonResponse(['success'=>true,'message'=>'Trip logged!','deduction'=>number_format($miles*$rate,2)]);

    case 'delete':
        $id   = (int)($input['id'] ?? 0);
        $stmt = db()->prepare("DELETE FROM mileage WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        if ($stmt->rowCount()===0) jsonResponse(['success'=>false,'message'=>'Not found.'],404);
        jsonResponse(['success'=>true,'message'=>'Trip deleted.']);

    default:
        jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
}
