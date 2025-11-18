<?php
require_once __DIR__ . '/middleware_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use App\DB;

$method = $_SERVER['REQUEST_METHOD'];
$pdo = DB::get();

// only admin allowed for write operations
if ($method !== 'GET' && ($current_user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error'=>'admin required']);
    exit;
}

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, name, stream_url, logo, category, is_active FROM channels ORDER BY id DESC');
    echo json_encode(['channels'=>$stmt->fetchAll()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
if ($method === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO channels (name, stream_url, logo, category, is_active) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$input['name'] ?? '', $input['stream_url'] ?? '', $input['logo'] ?? '', $input['category'] ?? '', intval($input['is_active'] ?? 1)]);
    echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
    exit;
}

if ($method === 'PUT') {
    $id = $input['id'] ?? 0;
    $stmt = $pdo->prepare('UPDATE channels SET name=?, stream_url=?, logo=?, category=?, is_active=? WHERE id=?');
    $stmt->execute([$input['name'] ?? '', $input['stream_url'] ?? '', $input['logo'] ?? '', $input['category'] ?? '', intval($input['is_active'] ?? 1), $id]);
    echo json_encode(['success'=>true]);
    exit;
}

if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $data);
    $id = $data['id'] ?? 0;
    $stmt = $pdo->prepare('DELETE FROM channels WHERE id=?');
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
    exit;
}
