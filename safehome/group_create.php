<?php
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/connect_db.php';

$userId = (int)$_SESSION['user_id'];
$error = "";
$ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupName = trim($_POST['group_name'] ?? '');

    if ($groupName === '') {
        $error = "グループ名を入力してください";
    } else {
        try {
            
            $invite = substr(bin2hex(random_bytes(8)), 0, 16);

            $pdo->beginTransaction();

            
            $stmt = $pdo->prepare("
                INSERT INTO groups (name, invite_code, owner_user_id)
                VALUES (:name, :code, :owner)
                RETURNING id
            ");
            $stmt->execute([
                ':name'  => $groupName,
                ':code'  => $invite,
                ':owner' => $userId
            ]);

            $groupId = (int)$stmt->fetchColumn();

            
            $stmt = $pdo->prepare("
                INSERT INTO group_members (group_id, user_id, role)
                VALUES (:gid, :uid, 'owner')
            ");
            $stmt->execute([
                ':gid' => $groupId,
                ':uid' => $userId
            ]);

            $pdo->commit();

            
            $_SESSION['group_id'] = $groupId;

            $ok = "グループを作成しました！";

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "作成エラー: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>グループ作成</title>
</head>
<body>

<h1>グループ作成</h1>
<?php if ($error): ?>
  <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php if ($ok): ?>
  <p style="color:green;"><?php echo htmlspecialchars($ok); ?></p>
  <p><a href="home.php">ホームへ</a></p>
<?php endif; ?>

<form method="post">
  <p>
    <label>グループ名</label><br>
    <input type="text" name="group_name" required>
  </p>
  <button type="submit">作成</button>
</form>

<p><a href="home.php">戻る</a></p>




</body>
</html>
