<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/connect_db.php';

if (!isset($_SESSION['group_id'])) {
    header('Location: group_create.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$gid = (int)$_SESSION['group_id'];

$error = "";
$ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $window = isset($_POST['window']) ? 1 : 0;
    $gas    = isset($_POST['gas']) ? 1 : 0;
    $aircon = isset($_POST['aircon']) ? 1 : 0;
    $tv     = isset($_POST['tv']) ? 1 : 0;
    $door   = isset($_POST['doorkey']) ? 1 : 0;
    $memo   = trim($_POST['memo'] ?? '');

    try {
        $stmt = $pdo->prepare("
            INSERT INTO home_state
              (group_id, user_id, window_closed, gas_off, aircon_off, tv_off, door_locked, memo)
            VALUES
              (:gid, :uid, :w, :g, :a, :t, :d, :m)
        ");

        $stmt->execute([
            ':gid' => $gid,
            ':uid' => $uid,
            ':w'   => $window,
            ':g'   => $gas,
            ':a'   => $aircon,
            ':t'   => $tv,
            ':d'   => $door,
            ':m'   => ($memo === '' ? null : $memo),
        ]);

        $ok = "保存しました！";
    } catch (PDOException $e) {
        $error = "保存エラー: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>入力画面</title>
</head>
<body>

<h1>入力画面</h1>

<?php if ($error): ?>
  <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<?php if ($ok): ?>
  <p style="color:green;"><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post">
  <label><input type="checkbox" name="window"> 窓</label><br>
  <label><input type="checkbox" name="gas"> ガス</label><br>
  <label><input type="checkbox" name="tv"> テレビ</label><br>
  <label><input type="checkbox" name="aircon"> エアコン</label><br>
  <label><input type="checkbox" name="doorkey"> 家の鍵</label><br><br>

  <label>メモ</label><br>
  <textarea name="memo" rows="3" cols="30"></textarea><br><br>

  <button type="submit">送信</button>
</form>

<p><a href="history.php">履歴を見る</a> / <a href="home.php">ホームへ</a></p>
</body>
</html>
