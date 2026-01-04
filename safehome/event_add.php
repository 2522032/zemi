<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_SESSION['group_id'])) { header('Location: group_select.php'); exit; }

$userId  = (int)$_SESSION['user_id'];
$groupId = (int)$_SESSION['group_id'];

// 所属チェック
$stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE user_id = :u AND group_id = :g");
$stmt->execute([':u' => $userId, ':g' => $groupId]);
if (!$stmt->fetchColumn()) { unset($_SESSION['group_id']); header('Location: group_select.php'); exit; }

$error = "";

// カレンダーから日付が渡される：?date=YYYY-MM-DD
$prefDate = trim($_GET['date'] ?? '');
$defaultStart = '';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $prefDate)) {
  // 例：その日の18:00をデフォルトにする（好みで変えてOK）
  $defaultStart = $prefDate . 'T18:00';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $desc  = trim($_POST['description'] ?? '');
  $start = trim($_POST['start_at'] ?? '');
  $end   = trim($_POST['end_at'] ?? '');

  if ($title === '' || $start === '') {
    $error = "タイトルと開始日時は必須です";
  } else {
    $startSql = str_replace('T', ' ', $start);
    $endSql   = ($end !== '') ? str_replace('T', ' ', $end) : null;

    $stmt = $pdo->prepare("
      INSERT INTO group_events (group_id, created_by, title, description, start_at, end_at)
      VALUES (:g, :u, :t, :d, :s, :e)
    ");
    $stmt->execute([
      ':g' => $groupId,
      ':u' => $userId,
      ':t' => $title,
      ':d' => $desc,
      ':s' => $startSql,
      ':e' => $endSql
    ]);

    // 追加した月を表示するために、開始日から y/m を作って戻る
    $dt = new DateTime($startSql);
    $y = $dt->format('Y');
    $m = $dt->format('n');
    header("Location: calendar.php?y={$y}&m={$m}");
    exit;
  }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>予定追加</title>
</head>
<body>
  <h1>予定を追加</h1>
  <p><a href="calendar.php">← カレンダーに戻る</a></p>

  <?php if ($error): ?>
    <p style="color:red;"><?= h($error) ?></p>
  <?php endif; ?>

  <form method="POST">
    <p>
      タイトル（必須）<br>
      <input type="text" name="title" style="width:320px;">
    </p>

    <p>
      開始日時（必須）<br>
      <input type="datetime-local" name="start_at" value="<?= h($defaultStart) ?>">
    </p>

    <p>
      終了日時（任意）<br>
      <input type="datetime-local" name="end_at">
    </p>

    <p>
      メモ（任意）<br>
      <textarea name="description" rows="4" cols="50"></textarea>
    </p>

    <button type="submit">保存</button>
  </form>
</body>
</html>
