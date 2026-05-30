<?php
// Повертає поточний контент-план проєкту у форматі воронки flows.
// URL: https://content.fineko.space/get-content-plan.php?token=...&projectId=2[&date_from=&date_to=]
// Дає змогу Content Manager читати АКТУАЛЬНИЙ план із дашборда (те саме джерело, що й запис).

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

$mcp = is_file(__DIR__ . '/../config/mcp.php') ? require __DIR__ . '/../config/mcp.php' : [];
$expected = $mcp['generation_webhook_token'] ?? '';

$hdr = $_SERVER['HTTP_X_IMPORT_TOKEN'] ?? ($_GET['token'] ?? '');
if ($expected === '' || !is_string($hdr) || !hash_equals((string) $expected, (string) $hdr)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$projectId = (int) ($_GET['projectId'] ?? $_GET['project_id'] ?? 0);
if ($projectId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_project']);
    exit;
}

try {
    $db = new Database($config);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_connect_failed']);
    exit;
}

// social network id -> platform key
$snById = [];
foreach ($db->query('SELECT id, name FROM social_networks')->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $snById[(int) $r['id']] = mb_strtolower((string) $r['name']);
}
function platKey($name)
{
    foreach (['threads', 'instagram', 'tiktok', 'linkedin'] as $k) {
        if (mb_strpos($name, $k) !== false) return $k;
    }
    return $name;
}

$where = 'project_id = ?';
$params = [$projectId];
if (!empty($_GET['date_from'])) { $where .= ' AND post_date >= ?'; $params[] = $_GET['date_from']; }
if (!empty($_GET['date_to']))   { $where .= ' AND post_date <= ?'; $params[] = $_GET['date_to']; }

$rows = $db->query(
    "SELECT id, post_date, social_network_id, text, generation_status FROM posts WHERE $where ORDER BY post_date ASC, id ASC",
    $params
)->fetchAll(PDO::FETCH_ASSOC);

$posts = [];
foreach ($rows as $r) {
    $posts[] = [
        'id'       => (string) $r['id'],
        'date'     => $r['post_date'],
        'platform' => platKey($snById[(int) $r['social_network_id']] ?? ''),
        'content'  => (string) $r['text'],
        'status'   => $r['generation_status'] ?: 'scheduled',
    ];
}

echo json_encode(['ok' => true, 'count' => count($posts), 'posts' => $posts]);
