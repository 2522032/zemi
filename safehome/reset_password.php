<?php
session_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/connect_db.php';

$error = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $code = trim($_POST['code'] ?? '');
  $new_password = $_POST['new_password'] ?? '';

  if ($username === '' || $code === '' || $new_password === '') {
    $error = '未入力の項目があります';
  } else {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $uid = $stmt->fetchColumn();

    if (!$uid) {
      $error = 'ユーザー名が見つかりません';
    } else {
      $stmt = $pdo->prepare("
        SELECT id, code_hash
        FROM password_reset_codes
        WHERE user_id = :uid
          AND used_at IS NULL
          AND expires_at > NOW()
        ORDER BY id DESC
        LIMIT 1
      ");
      $stmt->execute([':uid' => (int)$uid]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$row) {
        $error = '有効な再設定コードがありません（期限切れの可能性）';
      } elseif (!password_verify($code, $row['code_hash'])) {
        $error = '再設定コードが違います';
      } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        $pdo->beginTransaction();

        $upd = $pdo->prepare("UPDATE users SET password_hash = :p WHERE id = :id");
        $upd->execute([':p' => $hash, ':id' => (int)$uid]);

        $used = $pdo->prepare("UPDATE password_reset_codes SET used_at = NOW() WHERE id = :id");
        $used->execute([':id' => (int)$row['id']]);

        $pdo->commit();

        $ok = 'パスワードを再設定しました。ログインしてください。';
        unset($_SESSION['reset_username']);
      }
    }
  }
}

$prefill = $_SESSION['reset_username'] ?? '';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>パスワード再設定 | SafeHome</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width:520px;">
  <h1 class="h4 mb-3">パスワード再設定</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($ok): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></div>
    <a class="btn btn-primary w-100" href="login.php">ログインへ</a>
  <?php else: ?>
    <form method="post">
      <label class="form-label">ユーザー名</label>
      <input class="form-control mb-3" name="username" required value="<?php echo htmlspecialchars($prefill, ENT_QUOTES, 'UTF-8'); ?>">

      <label class="form-label">再設定コード（6桁）</label>
      <input class="form-control mb-3" name="code" required placeholder="例：123456">

      <label class="form-label">新しいパスワード</label>
      <input class="form-control mb-4" type="password" name="new_password" required>

      <button class="btn btn-primary w-100">再設定する</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
