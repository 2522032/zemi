<?php
declare(strict_types=1);

session_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php?msg=method_not_allowed');
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

if ($groupId <= 0) {
    header('Location: home.php?msg=no_group_id');
    exit;
}

try {
    $pdo->beginTransaction();

    // メンバーか確認
    $stmt = $pdo->prepare("
        SELECT 1
        FROM group_members
        WHERE group_id = :gid AND user_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':gid' => $groupId, ':uid' => $userId]);

    if (!$stmt->fetchColumn()) {
        $pdo->rollBack();
        header('Location: home.php?msg=not_member');
        exit;
    }

    // 退会
    $stmt = $pdo->prepare("
        DELETE FROM group_members
        WHERE group_id = :gid AND user_id = :uid
    ");
    $stmt->execute([':gid' => $groupId, ':uid' => $userId]);

    // ここが超重要：本当に消えたか
    $deleted = $stmt->rowCount();
    if ($deleted === 0) {
        $pdo->rollBack();
        header('Location: home.php?msg=delete_failed');
        exit;
    }

    // セッションのgroup_idがこのグループなら外す
    if (isset($_SESSION['group_id']) && (int)$_SESSION['group_id'] === $groupId) {
        unset($_SESSION['group_id']);
    }

    $pdo->commit();

    // 退会後は必ず選択画面へ（自動で別グループに入らない）
    header('Location: group_select.php?msg=left_group');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
