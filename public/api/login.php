<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use App\DB;
use App\Jwt;

header('Content-Type: application/json; charset=utf-8');
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error'=>'username and password required']);
    exit;
}

$pdo = DB::get();
$stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error'=>'invalid credentials']);
    exit;
}

$now = time();
$exp = $now + intval(getenv('JWT_EXPIRES') ?: 86400);
$payload = [
    'sub' => $user['id'],
    'iat' => $now,
    'exp' => $exp,
    'role' => $user['role']
];

$token = Jwt::generate($payload);
echo json_encode(['token'=>$token, 'expires'=>$exp]);
