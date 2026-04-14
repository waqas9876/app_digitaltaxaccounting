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
        $title    = trim($input['title'] ?? '');
        $content  = trim($input['content'] ?? '');
        $cat      = trim($input['category'] ?? 'General');
        $color    = trim($input['color'] ?? '#FF7421');
        $isPinned = (int)($input['is_pinned'] ?? 0);

        if (!$title) jsonResponse(['success'=>false,'message'=>'Title is required.']);

        // Sanitize color
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) $color = '#FF7421';

        $stmt = db()->prepare("INSERT INTO notes (client_id,title,content,category,color,is_pinned) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$clientId,$title,$content,$cat,$color,$isPinned]);
        jsonResponse(['success'=>true,'message'=>'Note saved!','id'=>db()->lastInsertId()]);

    case 'get':
        $id   = (int)($input['id'] ?? 0);
        $stmt = db()->prepare("SELECT * FROM notes WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        $row  = $stmt->fetch();
        if (!$row) jsonResponse(['success'=>false,'message'=>'Not found.'],404);
        jsonResponse(['success'=>true,'data'=>$row]);

    case 'update':
        $id      = (int)($input['id'] ?? 0);
        $title   = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $cat     = trim($input['category'] ?? 'General');

        if (!$title) jsonResponse(['success'=>false,'message'=>'Title required.']);

        $stmt = db()->prepare("UPDATE notes SET title=?,content=?,category=? WHERE id=? AND client_id=?");
        $stmt->execute([$title,$content,$cat,$id,$clientId]);
        jsonResponse(['success'=>true,'message'=>'Note updated!']);

    case 'pin':
        $id       = (int)($input['id'] ?? 0);
        $isPinned = (int)($input['is_pinned'] ?? 0);
        $stmt = db()->prepare("UPDATE notes SET is_pinned=? WHERE id=? AND client_id=?");
        $stmt->execute([$isPinned,$id,$clientId]);
        jsonResponse(['success'=>true]);

    case 'delete':
        $id   = (int)($input['id'] ?? 0);
        $stmt = db()->prepare("DELETE FROM notes WHERE id=? AND client_id=?");
        $stmt->execute([$id,$clientId]);
        jsonResponse(['success'=>true,'message'=>'Note deleted.']);

    default:
        jsonResponse(['success'=>false,'message'=>'Invalid action.'],400);
}
