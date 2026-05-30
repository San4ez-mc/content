<?php
// Returns situational media for a project (last 7 days, newest first).
// GET ?project_id=2  — requires active PHP session (user logged in)

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$projectId = (int) ($_GET['project_id'] ?? 0);
if ($projectId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_project_id']);
    exit;
}

$config = require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = new Database($config);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_connect_failed']);
    exit;
}

try {
    $stmt = $db->prepare(
        "SELECT id, type, file_path, caption, created_at
         FROM situational_media
         WHERE project_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY created_at DESC
         LIMIT 100"
    );
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table may not exist yet (no media sent from bot)
    $rows = [];
}

echo json_encode(['ok' => true, 'items' => $rows]);
