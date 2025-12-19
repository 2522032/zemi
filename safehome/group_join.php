<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/connect_db.php';

$uid = (int)$_SESSION['user_id'];
$error = "";
$ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['invite_code'] ?? '');

    if ($code === '') {
        $error = '招待コードを入力してください';
    } else {
        try {
            
            $stmt = $pdo->prepare("SELECT id, name FROM groups WHERE invite_code = :c");
            $stmt->execute([':c' => $code]);
            $g = $stmt->fetch();

            if (!$g) {
                $error = '招待コードが見つかりません';
            } else {
                $gid = (int)$g['id'];

                
                $stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = :gid AND user_id = :uid");
                $stmt->execute([':gid' => $gid, ':uid' => $uid]);
                $already = (bool)$stmt->fetchColumn();

                if (!$already) {
                    $stmt = $pdo->prepare("
                        INSERT INTO group_members (group_id, user_id, role)
                        VALUES (:gid, :uid, 'member')
                    ");
                    $stmt->execute([':gid' => $gid, ':uid' => $uid]);
                }

                
                $_SESSION['group_id'] = $gid;

                $ok = '参加しました：' . $g['name'];
            }
        } catch (PDOException $e) {
            $error = '参加エラー: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head><meta charset="utf-8"><title>グループ参加</title></head>
<body>
<h1>グループ参加（招待コード）</h1>

<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<?php if ($ok): ?>
  <p style="color:green;"><?php echo htmlspecialchars($ok); ?></p>
  <p><a href="home.php">ホームへ</a></p>
<?php endif; ?>

<form method="post">
  <label>招待コード</label><br>
  <input type="text" name="invite_code" required>
  <button type="submit">参加</button>
</form>

<p><a href="home.php">戻る</a></p>

</body>
</html>
