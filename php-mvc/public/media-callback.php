<?php
// Callback endpoint: content funnel reports media generation result.
// URL: https://content.fineko.space/media-callback.php
// Method: POST
// Auth: header X-Import-Token or ?token=
// Body: {
//   "postId": 123,          -- DB post id to attach media to
//   "projectId": 2,
//   "status": "done"|"failed",
//   "mediaUrl": "https://...",   -- public URL of generated image/video
//   "mediaType": "image"|"video",
//   "errorMessage": "..."        -- only when status=failed
// }
// On success: saves mediaUrl to posts.image_path, fires platform event media_done_{postId}
// Returns: { "ok": true }

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

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

$postId    = (int) ($body['postId'] ?? 0);
$projectId = (int) ($body['projectId'] ?? 0);
$status    = (string) ($body['status'] ?? 'done');
$mediaUrl  = (string) ($body['mediaUrl'] ?? '');

if ($postId <= 0 || $projectId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_postId_or_projectId']);
    exit;
}

try {
    $db = new Database($config);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_connect_failed']);
    exit;
}

if ($status === 'done' && $mediaUrl !== '') {
    try {
        $db->query(
            'UPDATE posts SET image_path = ?, updated_at = NOW() WHERE id = ? AND project_id = ?',
            [$mediaUrl, $postId, $projectId]
        );
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Fire platform webhook event so the funnel Wait node can resume.
// The platform engine listens on POST /api/events with { event, botId, data }.
$platformEventUrl = 'http://localhost:3000/api/events';
$platformApiKey   = $mcp['platform_api_key'] ?? '';
$eventPayload = json_encode([
    'event'  => 'media_done_' . $postId,
    'data'   => [
        'postId'   => $postId,
        'status'   => $status,
        'mediaUrl' => $mediaUrl,
        'error'    => $body['errorMessage'] ?? null,
    ],
]);

// Best-effort: we don't block the response on this
$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nX-Api-Key: $platformApiKey\r\n",
        'content' => $eventPayload,
        'timeout' => 3,
        'ignore_errors' => true,
    ],
]);
@file_get_contents($platformEventUrl, false, $ctx);

echo json_encode(['ok' => true, 'postId' => $postId, 'status' => $status]);
