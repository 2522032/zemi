<?php
session_start();

require_once __DIR__ . '/connect_db.php';

$groupName = null;

if (!isset($_SESSION['group_id'])) {
    header('Location: group_select.php'); 
    exit;
}

if (isset($_SESSION['group_id'])) {
    $stmt = $pdo->prepare("
        SELECT name
        FROM groups
        WHERE id = :gid
    ");
    $stmt->execute([':gid' => $_SESSION['group_id']]);
    $groupName = $stmt->fetchColumn();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>SafeHome | ホーム</title>
</head>
<body>

<h1>SafeHome</h1>

<p>
  ようこそ、
  <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>
  さん
</p>

<?php if ($groupName): ?>
  <p>
    現在のグループ：
    <strong><?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></strong>
  </p>
<?php else: ?>
  <p style="color:red;">グループ未選択</p>
<?php endif; ?>

<?php

$inviteCode = null;

if (isset($_SESSION['group_id'])) {
    $stmt = $pdo->prepare("SELECT invite_code FROM groups WHERE id = :gid");
    $stmt->execute([':gid' => $_SESSION['group_id']]);
    $inviteCode = $stmt->fetchColumn();
}
?>
<?php if ($inviteCode): ?>
  <p>招待コード：<strong><?php echo htmlspecialchars($inviteCode, ENT_QUOTES, 'UTF-8'); ?></strong></p>
<?php endif; ?>

<hr>

<ul>
  
  <li><a href="insert.php">安全チェック入力</a></li>
  <li><a href="history.php">履歴を見る</a></li>
  <li><a href="group_join.php">グループ参加（招待コード）</a></li>

</ul>

<hr>

<p><a href="logout.php">ログアウト</a></p>

</body>
</html>
