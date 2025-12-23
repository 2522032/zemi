<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

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

// 所属チェック
$stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE user_id = :u AND group_id = :g");
$stmt->execute([':u' => $userId, ':g' => $groupId]);
if (!$stmt->fetchColumn()) {
    unset($_SESSION['group_id']);
    header('Location: group_select.php');
    exit;
}

// グループ名
$stmt = $pdo->prepare("SELECT name FROM groups WHERE id = :gid");
$stmt->execute([':gid' => $groupId]);
$groupName = $stmt->fetchColumn() ?: '(不明なグループ)';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>グループチャット</title>
</head>
<body>

<h1>グループチャット</h1>
<p>グループ：<?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></p>

<p><a href="home.php">← Homeに戻る</a></p>

<?php
// ここでチャットUIを表示（あなたが作った chat_widget.php を使う）
require_once __DIR__ . '/chat_widget.php';
?>

</body>
</html>
