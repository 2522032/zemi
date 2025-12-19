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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gid = (int)($_POST['group_id'] ?? 0);

    if ($gid <= 0) {
        $error = "グループを選んでください";
    } else {
        
        $stmt = $pdo->prepare("
            SELECT 1
            FROM group_members
            WHERE user_id = :uid AND group_id = :gid
        ");
        $stmt->execute([':uid' => $uid, ':gid' => $gid]);

        if (!$stmt->fetchColumn()) {
            $error = "そのグループに参加していません";
        } else {
            $_SESSION['group_id'] = $gid;
            header('Location: home.php');
            exit;
        }
    }
}


$stmt = $pdo->prepare("
    SELECT g.id, g.name, gm.role, g.invite_code
    FROM group_members gm
    JOIN groups g ON g.id = gm.group_id
    WHERE gm.user_id = :uid
    ORDER BY gm.joined_at ASC
");
$stmt->execute([':uid' => $uid]);
$groups = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>グループ選択</title>
</head>
<body>
<h1>グループ選択</h1>

<?php if ($error): ?>
  <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (count($groups) === 0): ?>
  <p>まだどのグループにも参加していません。</p>
  <ul>
    <li><a href="group_create.php">グループを作成</a></li>
    <li><a href="group_join.php">グループ参加（招待コード）</a></li>


    <p><a href="logout.php">ログアウト</a></p>
  </ul>
<?php else: ?>
  <form method="post">
    <p>参加しているグループ：</p>
    <?php foreach ($groups as $g): ?>
      <label style="display:block; margin:6px 0;">
        <input type="radio" name="group_id" value="<?php echo (int)$g['id']; ?>"
          <?php echo (isset($_SESSION['group_id']) && (int)$_SESSION['group_id'] === (int)$g['id']) ? 'checked' : ''; ?>>
        <?php echo htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8'); ?>
        (<?php echo htmlspecialchars($g['role'], ENT_QUOTES, 'UTF-8'); ?>)
      </label>
    <?php endforeach; ?>
    <button type="submit">このグループを使う</button>
  </form>

  <hr>
  <ul>
    <li><a href="group_create.php">グループを作成</a></li>
    <li><a href="group_join.php">グループ参加（招待コード）</a></li>
    <li><a href="home.php">ホームへ戻る</a></li>
  </ul>
<?php endif; ?>

</body>
</html>
