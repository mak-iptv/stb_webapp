<?php
// epg.php - simple XMLTV generation (demo)
require_once __DIR__ . '/auth_middleware.php';

$channels = [
    ['id'=>1, 'name'=>'Demo Channel']
];

$events = [
    ['channel_id'=>1, 'start'=>date('Y-m-d H:i:00'), 'stop'=>date('Y-m-d H:i:00', time()+3600), 'title'=>'Demo Show', 'desc'=>'A demo program']
];

header('Content-Type: text/xml; charset=utf-8');
echo "<?xml version="1.0" encoding="UTF-8"?>\n";
echo "<tv>\n";
foreach ($channels as $c) {
    echo "<channel id="{$c['id']}"><display-name>{$c['name']}</display-name></channel>\n";
}
foreach ($events as $e) {
    $start = date('YmdHis O', strtotime($e['start']));
    $stop  = date('YmdHis O', strtotime($e['stop']));
    echo "<programme start="$start" stop="$stop" channel="{$e['channel_id']}">";
    echo "<title>{$e['title']}</title>";
    echo "<desc>{$e['desc']}</desc>";
    echo "</programme>\n";
}
echo "</tv>";
