<?php
require_once __DIR__ . '/middleware_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use App\DB;

$pdo = DB::get();
$stmt = $pdo->query('SELECT id, name, stream_url, logo, category, is_active FROM channels WHERE is_active=1 ORDER BY name');
$channels = $stmt->fetchAll();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['channels'=>$channels], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
