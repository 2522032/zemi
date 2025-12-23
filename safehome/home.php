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

<?php
    $members = [];
    if (isset($_SESSION['group_id'])) {
        $stmt = $pdo->prepare("
            SELECT u.username
            FROM users u
            JOIN group_members gm ON u.id = gm.user_id
            WHERE gm.group_id = :gid
            ORDER BY u.username ASC
        ");
        $stmt->execute([':gid' => $_SESSION['group_id']]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
?>
<?php if ($members): ?>
  <h4>グループメンバー</h4>
  <ul>
    <?php foreach ($members as $m): ?>
      <li><?php echo htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<hr>

<ul>
  
  <li><a href="insert.php">安全チェック入力</a></li>
  <li><a href="history.php">履歴を見る</a></li>
  <li><a href="group_select.php">グループ選択</a></li>
  <li><a href="chat.php">グループチャット</a></li>
  <li><a href="calendar.php">カレンダー（予定共有）</a></li>



</ul>

<hr>

<p><a href="logout.php">ログアウト</a></p>

</body>
</html>
