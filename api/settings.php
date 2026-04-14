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
    case 'update_profile':
        $firstName = trim($input['first_name'] ?? '');
        $lastName  = trim($input['last_name'] ?? '');
        $email     = strtolower(trim($input['email'] ?? ''));
        $phone     = trim($input['phone'] ?? '');
        $city      = trim($input['city'] ?? '');
        $state     = trim($input['state'] ?? '');
        $zip       = trim($input['zip_code'] ?? '');
        $address   = trim($input['address'] ?? '');

        if (!$firstName || !$lastName) jsonResponse(['success'=>false,'message'=>'First and last name required.']);
        if (!validateEmail($email)) jsonResponse(['success'=>false,'message'=>'Invalid email address.']);

        // Check email not taken by another client
        $check = db()->prepare("SELECT id FROM clients WHERE email=? AND id!=?");
        $check->execute([$email,$clientId]);
        if ($check->fetch()) jsonResponse(['success'=>false,'message'=>'Email is already in use.']);

        $stmt = db()->prepare("UPDATE clients SET first_name=?,last_name=?,email=?,phone=?,city=?,state=?,zip_code=?,address=? WHERE id=?");
        $stmt->execute([$firstName,$lastName,$email,$phone,$city,$state,$zip,$address,$clientId]);

        // Update session
        $_SESSION['client_name']  = $firstName . ' ' . $lastName;
        $_SESSION['client_email'] = $email;

        jsonResponse(['success'=>true,'message'=>'Profile updated successfully!']);

    case 'update_business':
        $bizName = trim($input['business_name'] ?? '');
        $bizType = trim($input['business_type'] ?? '');
        $stmt = db()->prepare("UPDATE clients SET business_name=?,business_type=? WHERE id=?");
        $stmt->execute([$bizName,$bizType,$clientId]);
        jsonResponse(['success'=>true,'message'=>'Business information updated!']);

    case 'change_password':
        $currentPwd  = $input['current_password'] ?? '';
        $newPwd      = $input['new_password'] ?? '';
        $confirmPwd  = $input['confirm_password'] ?? '';

        if ($newPwd !== $confirmPwd) jsonResponse(['success'=>false,'message'=>'Passwords do not match.']);
        $errors = validatePassword($newPwd);
        if (!empty($errors)) jsonResponse(['success'=>false,'message'=>implode(' ',$errors)]);

        $stmt = db()->prepare("SELECT password FROM clients WHERE id=?");
        $stmt->execute([$clientId]);
        $row = $stmt->fetch();
        if (!password_verify($currentPwd, $row['password'])) {
            jsonResponse(['success'=>false,'message'=>'Current password is incorrect.']);
        }

        $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost'=>12]);
        db()->prepare("UPDATE clients SET password=? WHERE id=?")->execute([$hash,$clientId]);
        jsonResponse(['success'=>true,'message'=>'Password changed successfully!']);

    default:
        jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
}
