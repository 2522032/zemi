<?php
session_start();
require_once __DIR__ . '/connect_db.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'login required']);
    exit;
}
if (!isset($_SESSION['group_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'group not selected']);
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$groupId = (int)$_SESSION['group_id'];
$sinceId = (int)($_GET['since_id'] ?? 0);


$stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE user_id = :u AND group_id = :g");
$stmt->execute([':u' => $userId, ':g' => $groupId]);
if (!$stmt->fetchColumn()) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT cm.id, cm.message, cm.created_at, u.username
    FROM chat_messages cm
    JOIN users u ON u.id = cm.user_id
    WHERE cm.group_id = :g AND cm.id > :since
    ORDER BY cm.id ASC
    LIMIT 50
");
$stmt->execute([':g' => $groupId, ':since' => $sinceId]);

echo json_encode([
    'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC),
]);
