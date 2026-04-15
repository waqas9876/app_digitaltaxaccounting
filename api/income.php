<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isClientLoggedIn()) {
    jsonResponse(['success'=>false,'message'=>'Unauthorized'],401);
}

$clientId = (int)$_SESSION['client_id'];

// Support both JSON and multipart/form-data
$isMultipart = isset($_POST['action']);
if ($isMultipart) {
    $input  = $_POST;
    $action = $input['action'] ?? '';
} else {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
}

if (!verifyCsrf($input['csrf_token'] ?? '')) {
    jsonResponse(['success'=>false,'message'=>'Invalid CSRF token.'],403);
}

// Handle file upload
function handleFileUpload(): ?string {
    if (empty($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES['receipt_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success'=>false,'message'=>'File upload error.']);
    }
    $allowed = ['image/png','application/pdf'];
    $mime    = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        jsonResponse(['success'=>false,'message'=>'Only PNG and PDF files are allowed.']);
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonResponse(['success'=>false,'message'=>'File size must be under 5MB.']);
    }
    $ext      = $mime === 'image/png' ? 'png' : 'pdf';
    $filename = uniqid('inc_', true) . '.' . $ext;
    $dir      = __DIR__ . '/../assets/uploads/income/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    move_uploaded_file($file['tmp_name'], $dir . $filename);
    return $filename;
}

switch ($action) {
    case 'create':
        $desc         = trim($input['description'] ?? '');
        $amount       = (float)($input['amount'] ?? 0);
        $date         = $input['income_date'] ?? date('Y-m-d');
        $cat          = trim($input['category'] ?? 'Employment Income');
        $method       = trim($input['payment_method'] ?? '');
        $reference    = trim($input['reference'] ?? '');
        $documentType = trim($input['document_type'] ?? '');
        $notes        = trim($input['notes'] ?? '');
        $taxYear      = (int)($input['tax_year'] ?? getTaxYear());

        if (!$desc || $amount <= 0) jsonResponse(['success'=>false,'message'=>'Description and amount are required.']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) jsonResponse(['success'=>false,'message'=>'Invalid date.']);

        $filename = handleFileUpload();

        $stmt = db()->prepare("INSERT INTO income (client_id,description,category,amount,income_date,payment_method,reference,document_type,receipt_file,notes,tax_year) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$clientId,$desc,$cat,$amount,$date,$method,$reference,$documentType,$filename,$notes,$taxYear]);
        jsonResponse(['success'=>true,'message'=>'Income added successfully!','id'=>db()->lastInsertId()]);

    case 'get':
        $id   = (int)($input['id'] ?? 0);
        $stmt = db()->prepare("SELECT * FROM income WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        $row  = $stmt->fetch();
        if (!$row) jsonResponse(['success'=>false,'message'=>'Record not found.'],404);
        jsonResponse(['success'=>true,'data'=>$row]);

    case 'update':
        $id           = (int)($input['id'] ?? 0);
        $desc         = trim($input['description'] ?? '');
        $amount       = (float)($input['amount'] ?? 0);
        $date         = $input['income_date'] ?? '';
        $cat          = trim($input['category'] ?? 'Employment Income');
        $method       = trim($input['payment_method'] ?? '');
        $reference    = trim($input['reference'] ?? '');
        $documentType = trim($input['document_type'] ?? '');
        $notes        = trim($input['notes'] ?? '');

        if (!$desc || $amount <= 0 || !$date) jsonResponse(['success'=>false,'message'=>'Missing required fields.']);

        $filename = handleFileUpload();

        if ($filename) {
            $stmt = db()->prepare("UPDATE income SET description=?,category=?,amount=?,income_date=?,payment_method=?,reference=?,document_type=?,receipt_file=?,notes=? WHERE id=? AND client_id=?");
            $stmt->execute([$desc,$cat,$amount,$date,$method,$reference,$documentType,$filename,$notes,$id,$clientId]);
        } else {
            $stmt = db()->prepare("UPDATE income SET description=?,category=?,amount=?,income_date=?,payment_method=?,reference=?,document_type=?,notes=? WHERE id=? AND client_id=?");
            $stmt->execute([$desc,$cat,$amount,$date,$method,$reference,$documentType,$notes,$id,$clientId]);
        }
        jsonResponse(['success'=>true,'message'=>'Income updated!']);

    case 'delete':
        $id   = (int)($input['id'] ?? 0);
        // Delete associated file
        $stmt = db()->prepare("SELECT receipt_file FROM income WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        $row = $stmt->fetch();
        if ($row && $row['receipt_file']) {
            $path = __DIR__ . '/../assets/uploads/income/' . $row['receipt_file'];
            if (file_exists($path)) unlink($path);
        }
        $stmt = db()->prepare("DELETE FROM income WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        if ($stmt->rowCount() === 0) jsonResponse(['success'=>false,'message'=>'Record not found.'],404);
        jsonResponse(['success'=>true,'message'=>'Income deleted.']);

    default:
        jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
}
