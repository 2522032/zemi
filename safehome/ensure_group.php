<?php

require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


if (isset($_SESSION['group_id'])) {
    return;
}

$userId = (int)$_SESSION['user_id'];

try {
    
    $stmt = $pdo->prepare("
        SELECT group_id
        FROM group_members
        WHERE user_id = :uid
        ORDER BY joined_at ASC
        LIMIT 1
    ");
    $stmt->execute([':uid' => $userId]);
    $gid = $stmt->fetchColumn();

    if ($gid) {
        $_SESSION['group_id'] = (int)$gid;
        return;
    }

    // 所属グループが無い場合はエラーにする（作成を促す）
    exit('グループが未作成です。先にグループを作成してください。');

} catch (PDOException $e) {
    exit("グループ確認エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
