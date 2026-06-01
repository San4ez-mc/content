<?php
// Update a single post by ID.
// URL: https://content.fineko.space/patch-post.php
// Method: POST (or PATCH — both accepted)
// Auth: header X-Import-Token or ?token=
// Body: { "id": 123, "projectId": 2, "text": "...", "audience": "cold", "post_type": "thread_single",
//         "slides": [...], "image_prompt": "...", "status": "scheduled" }
// Returns: { "ok": true, "updated": 1 }

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

$postId    = (int) ($body['id'] ?? 0);
$projectId = (int) ($body['projectId'] ?? $body['project_id'] ?? 0);
if ($postId <= 0 || $projectId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_id_or_project']);
    exit;
}

try {
    $db = new Database($config);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_connect_failed']);
    exit;
}

// Build SET clause only for fields that were explicitly provided
$allowed = ['text', 'audience', 'post_type', 'image_prompt', 'status'];
$sets = [];
$params = [];

foreach ($allowed as $field) {
    if (array_key_exists($field, $body)) {
        $sets[] = "$field = ?";
        $params[] = $body[$field] === null ? null : (string) $body[$field];
    }
}

// slides is JSON
if (array_key_exists('slides', $body)) {
    $sets[] = 'slides = ?';
    $params[] = $body['slides'] === null ? null : json_encode($body['slides'], JSON_UNESCAPED_UNICODE);
}

if (empty($sets)) {
    echo json_encode(['ok' => true, 'updated' => 0, 'note' => 'nothing_to_update']);
    exit;
}

$sets[] = 'updated_at = NOW()';
$params[] = $postId;
$params[] = $projectId;

try {
    $db->query(
        'UPDATE posts SET ' . implode(', ', $sets) . ' WHERE id = ? AND project_id = ?',
        $params
    );
    echo json_encode(['ok' => true, 'updated' => 1]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
