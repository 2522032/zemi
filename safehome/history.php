<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_SESSION['group_id'])) { header('Location: group_create.php'); exit; }

require_once __DIR__ . '/connect_db.php';

$gid = (int)$_SESSION['group_id'];

$stmt = $pdo->prepare("
  SELECT h.checked_at,
         u.username,
         h.window_closed, h.gas_off, h.aircon_off, h.tv_off, h.door_locked,
         h.memo
  FROM home_state h
  JOIN users u ON u.id = h.user_id
  WHERE h.group_id = :gid
  ORDER BY h.checked_at DESC
  LIMIT 50
");
$stmt->execute([':gid' => $gid]);
$rows = $stmt->fetchAll();


?>
<!doctype html>
<html lang="ja">
<head><meta charset="utf-8"><title>履歴</title></head>
<body>
<h1>履歴</h1>

<table border="1" cellpadding="6">
<tr>
  <th>入力者<th><th>日時</th><th>窓</th><th>ガス</th><th>エアコン</th><th>TV</th><th>鍵</th><th>メモ</th>
</tr>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?php echo htmlspecialchars($r['checked_at'], ENT_QUOTES, 'UTF-8'); ?></td>
  <td><?php echo $r['window_closed'] ? '○' : '—'; ?></td>
  <td><?php echo $r['gas_off'] ? '○' : '—'; ?></td>
  <td><?php echo $r['aircon_off'] ? '○' : '—'; ?></td>
  <td><?php echo $r['tv_off'] ? '○' : '—'; ?></td>
  <td><?php echo $r['door_locked'] ? '○' : '—'; ?></td>
  <td><?php echo htmlspecialchars($r['memo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
  <td><?php echo htmlspecialchars($r['username'], ENT_QUOTES, 'UTF-8'); ?></td>

</tr>
<?php endforeach; ?>
</table>

<p><a href="insert.php">入力へ</a> / <a href="home.php">ホームへ</a></p>
</body>
</html>
