<?php
// Standalone batch import endpoint for content plans pushed from the flows funnel.
// URL: https://content.fineko.space/import-content-plan.php
// Auth: header X-Import-Token or ?token=  (matches mcp.php generation_webhook_token)
// Body: { "projectId": 2, "posts": [ {date, time, platform, content|text, post_type?} ] }
//   (also accepts { "plan": { "posts": [...] } })

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

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

$projectId = (int) ($body['projectId'] ?? $body['project_id'] ?? 0);
$posts = $body['posts'] ?? ($body['plan']['posts'] ?? []);
if ($projectId <= 0 || !is_array($posts)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_project_or_posts']);
    exit;
}

try {
    $db = new Database($config);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_connect_failed']);
    exit;
}

// Build social-network name -> id map (social_networks is a global table, shared across projects)
$snByName = [];
try {
    $rows = $db->query('SELECT id, name FROM social_networks ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $snByName[mb_strtolower($r['name'])] = (int) $r['id'];
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'sn_query_failed']);
    exit;
}

function resolveSn($platform, $snByName)
{
    $p = mb_strtolower(trim((string) $platform));
    if ($p === '') return 0;
    // alias normalisation
    $aliases = [
        'threads' => 'threads',
        'instagram' => 'instagram',
        'instagram posts' => 'instagram posts',
        'instagram stories' => 'stories',
        'stories' => 'stories',
        'ig' => 'instagram',
        'tiktok' => 'tiktok',
        'linkedin' => 'linkedin',
    ];
    $needle = $aliases[$p] ?? $p;
    foreach ($snByName as $name => $id) {
        if (mb_strpos($name, $needle) !== false) return $id;
    }
    // fallback: first network
    return $snByName ? (int) reset($snByName) : 0;
}

$inserted = 0;
$skipped = 0;
$errors = [];
$unmatchedPlatforms = [];

foreach ($posts as $post) {
    if (!is_array($post)) { $skipped++; continue; }
    $date = trim((string) ($post['date'] ?? ''));
    $text = (string) ($post['content'] ?? $post['text'] ?? '');
    $platform = $post['platform'] ?? 'threads';
    $snId = resolveSn($platform, $snByName);

    if ($date === '' || $text === '' || $snId <= 0) {
        $skipped++;
        if ($snId <= 0) $unmatchedPlatforms[(string) $platform] = true;
        continue;
    }

    $slides   = isset($post['slides']) && is_array($post['slides']) ? json_encode($post['slides'], JSON_UNESCAPED_UNICODE) : null;
    $audience = isset($post['audience']) ? substr((string)$post['audience'], 0, 20) : null;
    $threadPart = isset($post['threadPart']) ? (int)$post['threadPart'] : null;
    $postType = isset($post['post_type']) ? substr((string)$post['post_type'], 0, 50) : null;

    try {
        $db->query(
            'INSERT INTO posts (project_id, post_date, social_network_id, text, slides, audience, thread_part, post_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$projectId, $date, $snId, $text, $slides, $audience, $threadPart, $postType]
        );
        $inserted++;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

echo json_encode([
    'ok' => true,
    'inserted' => $inserted,
    'skipped' => $skipped,
    'unmatched_platforms' => array_keys($unmatchedPlatforms),
    'errors' => $errors,
]);
