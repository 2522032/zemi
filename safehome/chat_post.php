<?php
session_start();
require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['group_id'])) {
    header('Location: group_select.php');
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$groupId = (int)$_SESSION['group_id'];
$msg     = trim($_POST['message'] ?? '');

if ($msg === '') {
    header('Location: home.php#chat');
    exit;
}

// グループ所属チェック（超重要）
$stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE user_id = :u AND group_id = :g");
$stmt->execute([':u' => $userId, ':g' => $groupId]);
if (!$stmt->fetchColumn()) {
    http_response_code(403);
    exit('このグループにアクセスできません');
}

$stmt = $pdo->prepare("
    INSERT INTO chat_messages (group_id, user_id, message)
    VALUES (:g, :u, :m)
");
$stmt->execute([':g' => $groupId, ':u' => $userId, ':m' => $msg]);

header('Location: home.php#chat');
exit;
