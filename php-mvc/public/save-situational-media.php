<?php
// Saves situational media (photo/video) received from the Telegram bot.
// POST with X-Import-Token header (same token as import-content-plan.php)
// Body: { "projectId": 2, "type": "photo", "fileUrl": "...", "caption": "...", "mimeType": "..." }

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
$type      = in_array($body['type'] ?? '', ['photo', 'video', 'animation']) ? $body['type'] : 'photo';
$fileUrl   = $body['fileUrl'] ?? '';
$caption   = substr((string) ($body['caption'] ?? ''), 0, 1000);
$mimeType  = (string) ($body['mimeType'] ?? '');

if ($projectId <= 0 || $fileUrl === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_fields']);
    exit;
}

try {
    $db = new Database($config);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_connect_failed']);
    exit;
}

// Ensure table exists (idempotent)
$db->query("CREATE TABLE IF NOT EXISTS situational_media (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    type        VARCHAR(20) NOT NULL DEFAULT 'photo',
    file_path   VARCHAR(500) NOT NULL,
    caption     TEXT,
    source      VARCHAR(50) NOT NULL DEFAULT 'bot',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proj_created (project_id, created_at)
)");

// Purge records older than 7 days
$db->query("DELETE FROM situational_media WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");

// Determine file extension
$ext = 'jpg';
if (str_contains($mimeType, 'png'))  $ext = 'png';
elseif (str_contains($mimeType, 'webp')) $ext = 'webp';
elseif (str_contains($mimeType, 'gif'))  $ext = 'gif';
elseif (str_contains($mimeType, 'video') || $type === 'video' || $type === 'animation') $ext = 'mp4';

$uploadDir = __DIR__ . '/uploads/situational/' . $projectId . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
$destPath = $uploadDir . $filename;
$webPath  = '/uploads/situational/' . $projectId . '/' . $filename;

// Download from Telegram CDN
$ctx = stream_context_create(['http' => ['timeout' => 20]]);
$fileData = @file_get_contents($fileUrl, false, $ctx);
if ($fileData === false || strlen($fileData) < 100) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'download_failed']);
    exit;
}
file_put_contents($destPath, $fileData);

$stmt = $db->prepare(
    "INSERT INTO situational_media (project_id, type, file_path, caption, source) VALUES (?,?,?,?,?)"
);
$stmt->execute([$projectId, $type, $webPath, $caption, 'bot']);
$id = $db->lastInsertId();

echo json_encode(['ok' => true, 'id' => (int) $id, 'path' => $webPath]);
