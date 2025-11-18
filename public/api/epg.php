<?php
require_once __DIR__ . '/middleware_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use App\DB;

$pdo = DB::get();
$stmt = $pdo->query('SELECT id, name FROM channels WHERE is_active=1');
$channels = $stmt->fetchAll();

$events = [];
// simple demo: one event per channel
foreach ($channels as $c) {
    $events[] = ['channel_id'=>$c['id'], 'start'=>date('Y-m-d H:00:00'), 'stop'=>date('Y-m-d H:59:59', time()+3600), 'title'=>'Demo Program for '.$c['name'], 'desc'=>'Automatically generated demo'];
}

header('Content-Type: text/xml; charset=utf-8');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<tv>\n";
foreach ($channels as $c) {
    echo "<channel id=\"{$c['id']}\"><display-name>{$c['name']}</display-name></channel>\n";
}
foreach($events as $e) {
    $start = date('YmdHis O', strtotime($e['start']));
    $stop  = date('YmdHis O', strtotime($e['stop']));
    echo "<programme start=\"$start\" stop=\"$stop\" channel=\"{$e['channel_id']}\">";
    echo "<title>{$e['title']}</title>";
    echo "<desc>{$e['desc']}</desc>";
    echo "</programme>\n";
}
echo "</tv>";
